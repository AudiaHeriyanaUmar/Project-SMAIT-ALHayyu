<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminClassManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_classes_and_assign_only_active_teachers_as_homeroom_teachers(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $teacher = User::factory()->create(['role' => 'guru']);

        $this->actingAs($admin)
            ->post(route('admin.classes.store'), [
                'nama_kelas' => 'X IPA 1',
                'wali_kelas_id' => $teacher->id,
            ])
            ->assertRedirect(route('admin.classes.index'));

        $this->assertDatabaseHas('kelas', [
            'nama_kelas' => 'X IPA 1',
            'wali_kelas_id' => $teacher->id,
        ]);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => 'class.created']);
    }

    public function test_promotion_moves_active_students_without_changing_historical_journal_class(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $sourceId = $this->createClass('X IPA 1');
        $targetId = $this->createClass('XI IPA 1');
        $studentIds = [];

        foreach (['1111111111', '1111111112'] as $index => $nisn) {
            $studentIds[] = DB::table('siswas')->insertGetId([
                'nisn' => $nisn,
                'nama_lengkap' => 'Siswa '.$index,
                'kelas_id' => $sourceId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $teacher = User::factory()->create(['role' => 'guru']);
        $subjectId = DB::table('mata_pelajarans')->insertGetId([
            'kode_mapel' => 'MAT-X',
            'nama_mapel' => 'Matematika',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $journalId = DB::table('jurnal_gurus')->insertGetId([
            'user_id' => $teacher->id,
            'tanggal' => now()->toDateString(),
            'jam_ke' => '1',
            'kelas_id' => $sourceId,
            'mata_pelajaran_id' => $subjectId,
            'materi_pembelajaran' => 'Materi',
            'catatan_kegiatan' => null,
            'status_monitoring' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.classes.promote'), [
                'source_class_id' => $sourceId,
                'target_class_id' => $targetId,
            ])
            ->assertRedirect(route('admin.classes.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('siswas', ['id' => $studentIds[0], 'kelas_id' => $targetId]);
        $this->assertDatabaseHas('siswas', ['id' => $studentIds[1], 'kelas_id' => $targetId]);
        $this->assertDatabaseHas('jurnal_gurus', ['id' => $journalId, 'kelas_id' => $sourceId]);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => 'class.promoted']);
    }

    public function test_class_promotion_refuses_to_mix_students_into_an_occupied_target(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $sourceId = $this->createClass('X IPA 1');
        $targetId = $this->createClass('XI IPA 1');

        foreach ([['1111111111', $sourceId], ['2222222222', $targetId]] as [$nisn, $classId]) {
            DB::table('siswas')->insert([
                'nisn' => $nisn,
                'nama_lengkap' => 'Siswa',
                'kelas_id' => $classId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->actingAs($admin)
            ->from(route('admin.classes.index'))
            ->post(route('admin.classes.promote'), [
                'source_class_id' => $sourceId,
                'target_class_id' => $targetId,
            ])
            ->assertRedirect(route('admin.classes.index'))
            ->assertSessionHasErrors('target_class_id');

        $this->assertDatabaseHas('siswas', ['nisn' => '1111111111', 'kelas_id' => $sourceId]);
    }

    private function createClass(string $name): int
    {
        return DB::table('kelas')->insertGetId([
            'nama_kelas' => $name,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
