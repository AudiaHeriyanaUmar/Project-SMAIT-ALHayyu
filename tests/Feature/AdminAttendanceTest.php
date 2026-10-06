<?php

namespace Tests\Feature;

use App\Models\AbsensiSiswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_filter_attendance_and_view_status_summaries(): void
    {
        [$admin, $guru, $classId, , $studentId, $journalId] = $this->createAttendanceFixture();
        DB::table('absensi_siswas')->insert([
            [
                'jurnal_guru_id' => $journalId,
                'siswa_id' => $studentId,
                'status' => 'hadir',
                'keterangan' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'jurnal_guru_id' => $journalId,
                'siswa_id' => DB::table('siswas')->insertGetId([
                    'nisn' => '1234567891',
                    'nama_lengkap' => 'Siswa Kedua',
                    'kelas_id' => $classId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]),
                'status' => 'alpa',
                'keterangan' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->actingAs($admin)
            ->get(route('admin.absensi.index', [
                'tanggal_dari' => '2026-10-01',
                'tanggal_sampai' => '2026-10-31',
                'kelas_id' => $classId,
                'guru_id' => $guru->id,
            ]))
            ->assertOk()
            ->assertSee('Rekap absensi siswa')
            ->assertViewHas('summary', fn (array $summary) => $summary['total'] === 2
                && $summary['hadir'] === 1
                && $summary['alpa'] === 1);

        $this->get(route('admin.absensi.index', ['siswa_id' => $studentId, 'status' => 'hadir']))
            ->assertOk()
            ->assertViewHas('attendance', fn ($rows) => $rows->total() === 1);
    }

    public function test_admin_correction_is_saved_with_actor_and_explanation(): void
    {
        [$admin, , , , , $journalId] = $this->createAttendanceFixture();
        $attendanceId = DB::table('absensi_siswas')->insertGetId([
            'jurnal_guru_id' => $journalId,
            'siswa_id' => DB::table('siswas')->value('id'),
            'status' => 'alpa',
            'keterangan' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->put(route('admin.absensi.correct', $attendanceId), [
                'status' => 'izin',
                'keterangan' => 'Surat diterima',
                'alasan' => 'Wali kelas mengonfirmasi surat izin.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('absensi_siswas', [
            'id' => $attendanceId,
            'status' => 'izin',
            'keterangan' => 'Surat diterima',
        ]);
        $this->assertDatabaseHas('attendance_corrections', [
            'absensi_siswa_id' => $attendanceId,
            'admin_id' => $admin->id,
            'admin_name' => $admin->name,
            'status_sebelumnya' => 'alpa',
            'status_baru' => 'izin',
            'alasan' => 'Wali kelas mengonfirmasi surat izin.',
        ]);

        $this->assertSame('izin', AbsensiSiswa::findOrFail($attendanceId)->status);
    }

    public function test_only_admin_can_view_print_or_correct_attendance(): void
    {
        [$admin, $guru, , , , $journalId] = $this->createAttendanceFixture();
        $attendanceId = DB::table('absensi_siswas')->insertGetId([
            'jurnal_guru_id' => $journalId,
            'siswa_id' => DB::table('siswas')->value('id'),
            'status' => 'hadir',
            'keterangan' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($guru)
            ->get(route('admin.absensi.index'))
            ->assertForbidden();
        $this->get(route('admin.absensi.cetak'))->assertForbidden();
        $this->put(route('admin.absensi.correct', $attendanceId), [
            'status' => 'alpa',
            'alasan' => 'Percobaan tanpa wewenang',
        ])->assertForbidden();

        $this->actingAs($admin)->get(route('admin.absensi.cetak'))->assertOk();
    }

    public function test_duplicate_attendance_per_student_and_journal_is_rejected_by_database(): void
    {
        [, , , , $studentId, $journalId] = $this->createAttendanceFixture();
        $attributes = [
            'jurnal_guru_id' => $journalId,
            'siswa_id' => $studentId,
            'status' => 'hadir',
            'keterangan' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('absensi_siswas')->insert($attributes);

        $this->expectException(\Illuminate\Database\QueryException::class);
        DB::table('absensi_siswas')->insert($attributes);
    }

    private function createAttendanceFixture(): array
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
        $studentId = DB::table('siswas')->insertGetId([
            'nisn' => '1234567890',
            'nama_lengkap' => 'Siswa Contoh',
            'kelas_id' => $classId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $journalId = DB::table('jurnal_gurus')->insertGetId([
            'user_id' => $guru->id,
            'tanggal' => '2026-10-07',
            'jam_ke' => '1-2',
            'kelas_id' => $classId,
            'mata_pelajaran_id' => $subjectId,
            'materi_pembelajaran' => 'Matematika',
            'catatan_kegiatan' => null,
            'status_monitoring' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$admin, $guru, $classId, $subjectId, $studentId, $journalId];
    }
}
