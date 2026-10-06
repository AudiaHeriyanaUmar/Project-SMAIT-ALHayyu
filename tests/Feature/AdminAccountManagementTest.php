<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAccountManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admins_can_access_account_management(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);

        $this->actingAs($guru)
            ->get(route('admin.accounts.index'))
            ->assertForbidden();

        $this->get(route('admin.accounts.create'))->assertForbidden();
        $this->post(route('admin.accounts.store'), [])->assertForbidden();
    }

    public function test_admin_can_create_edit_and_reset_an_account_password(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $this->get(route('admin.accounts.create'))
            ->assertOk()
            ->assertSee('Tambah akun baru');

        $this->post(route('admin.accounts.store'), [
            'name' => 'Guru Baru',
            'email' => 'guru.baru@example.test',
            'role' => 'guru',
            'password' => 'password-baru-123',
            'password_confirmation' => 'password-baru-123',
        ])->assertRedirect(route('admin.accounts.index'));

        $account = User::query()->where('email', 'guru.baru@example.test')->firstOrFail();
        $this->assertSame('guru', $account->role);
        $this->assertNotNull($account->email_verified_at);
        $this->assertTrue(Hash::check('password-baru-123', $account->password));
        $this->get(route('admin.accounts.edit', $account))->assertOk();
        $this->get(route('admin.accounts.password.edit', $account))->assertOk();

        $this->put(route('admin.accounts.update', $account), [
            'name' => 'Guru Diperbarui',
            'email' => 'guru.updated@example.test',
            'role' => 'calon_siswa',
        ])->assertRedirect(route('admin.accounts.index'));

        $account->refresh();
        $this->assertSame('Guru Diperbarui', $account->name);
        $this->assertSame('calon_siswa', $account->role);

        $this->put(route('admin.accounts.password.update', $account), [
            'password' => 'password-reset-123',
            'password_confirmation' => 'password-reset-123',
        ])->assertRedirect(route('admin.accounts.index'));

        $this->assertTrue(Hash::check('password-reset-123', $account->refresh()->password));
        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => 'account.created',
            'subject_id' => (string) $account->id,
        ]);
        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => 'account.updated',
            'subject_id' => (string) $account->id,
        ]);
        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => 'account.password_changed',
            'subject_id' => (string) $account->id,
            'metadata' => null,
        ]);
    }

    public function test_admin_can_monitor_online_status_and_active_time(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $guru = User::factory()->create(['role' => 'guru']);
        DB::table('account_activity_daily')->insert([
            'user_id' => $guru->id,
            'activity_date' => now()->toDateString(),
            'active_seconds' => 3900,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('account_activity_sessions')->insert([
            'user_id' => $guru->id,
            'session_hash' => hash('sha256', 'live-session'),
            'started_at' => now()->subHour(),
            'last_activity_at' => now(),
            'ended_at' => null,
            'active_seconds' => 3900,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.accounts.index'))
            ->assertOk()
            ->assertSee($guru->email)
            ->assertSee('Online')
            ->assertSee('1j 05m');
    }

    public function test_admin_can_archive_an_account_without_deleting_its_journals(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $guru = User::factory()->create(['role' => 'guru']);
        $classId = DB::table('kelas')->insertGetId([
            'nama_kelas' => 'X IPA 1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $subjectId = DB::table('mata_pelajarans')->insertGetId([
            'kode_mapel' => 'MAT-X',
            'nama_mapel' => 'Matematika',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $journalId = DB::table('jurnal_gurus')->insertGetId([
            'user_id' => $guru->id,
            'tanggal' => now()->toDateString(),
            'jam_ke' => '1-2',
            'kelas_id' => $classId,
            'mata_pelajaran_id' => $subjectId,
            'materi_pembelajaran' => 'Aljabar',
            'status_monitoring' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.accounts.destroy', $guru))
            ->assertRedirect(route('admin.accounts.index'));

        $this->assertSoftDeleted('users', ['id' => $guru->id]);
        $this->assertDatabaseHas('jurnal_gurus', ['id' => $journalId, 'user_id' => $guru->id]);
        $this->assertSame($guru->name, \App\Models\JurnalGuru::findOrFail($journalId)->user->name);
    }

    public function test_admin_cannot_remove_themselves_or_the_last_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->put(route('admin.accounts.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => 'guru',
            ])
            ->assertSessionHasErrors('role');

        $this->actingAs($admin)
            ->delete(route('admin.accounts.destroy', $admin))
            ->assertSessionHasErrors('account');

        $guru = User::factory()->create(['role' => 'guru']);
        $this->delete(route('admin.accounts.destroy', $guru))
            ->assertRedirect(route('admin.accounts.index'));

        $this->assertSoftDeleted('users', ['id' => $guru->id]);
        $this->assertSame('admin', $admin->refresh()->role);
    }
}
