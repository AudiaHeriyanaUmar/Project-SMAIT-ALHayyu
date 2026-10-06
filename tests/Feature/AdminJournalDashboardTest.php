<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminJournalDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admins_can_access_journal_monitoring_routes(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);

        $this->actingAs($guru)
            ->get(route('admin.dashboard'))
            ->assertForbidden();

        $this->get(route('admin.jurnal.index'))->assertForbidden();
        $this->post(route('admin.jurnal.verify', 1))->assertForbidden();
        $this->get(route('admin.jurnal.cetak'))->assertForbidden();

        $candidate = User::factory()->create(['role' => 'calon_siswa']);
        $this->actingAs($candidate)
            ->get(route('admin.dashboard'))
            ->assertForbidden();

        auth()->logout();
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_admin_can_view_the_monitoring_dashboard_and_legacy_journal_url(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Monitoring jurnal guru')
            ->assertViewHas('summary');

        $this->get(route('admin.jurnal.index'))->assertOk();
    }

    public function test_dashboard_summarizes_and_filters_journals_by_month_and_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $guru = User::factory()->create(['role' => 'guru']);
        $kelasId = DB::table('kelas')->insertGetId([
            'nama_kelas' => 'X IPA 1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $mapelId = DB::table('mata_pelajarans')->insertGetId([
            'kode_mapel' => 'MAT-X',
            'nama_mapel' => 'Matematika',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach (['pending', 'verified'] as $status) {
            DB::table('jurnal_gurus')->insert([
                'user_id' => $guru->id,
                'tanggal' => now()->startOfMonth()->toDateString(),
                'jam_ke' => '1-2',
                'kelas_id' => $kelasId,
                'mata_pelajaran_id' => $mapelId,
                'materi_pembelajaran' => 'Aljabar',
                'catatan_kegiatan' => null,
                'status_monitoring' => $status,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->actingAs($admin)
            ->get(route('admin.dashboard', [
                'bulan' => now()->month,
                'status' => 'pending',
            ]))
            ->assertOk()
            ->assertViewHas('summary', fn (array $summary) => $summary['total'] === 2
                && $summary['pending'] === 1
                && $summary['verified'] === 1
                && $summary['guru'] === 1)
            ->assertViewHas('jurnals', fn ($jurnals) => $jurnals->total() === 1
                && $jurnals->first()->status_monitoring === 'pending');
    }
}
