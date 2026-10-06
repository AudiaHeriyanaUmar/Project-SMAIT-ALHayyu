<?php

namespace Tests\Feature;

use App\Models\AbsensiSiswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
        $this->get(route('admin.absensi.report'))->assertForbidden();
        $this->get(route('admin.absensi.report.export'))->assertForbidden();
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

    public function test_monthly_attendance_report_calculates_presence_and_repeated_absence_and_exports_csv(): void
    {
        [$admin, $teacher, $classId, $subjectId, $studentId, $journalId] = $this->createAttendanceFixture();
        DB::table('absensi_siswas')->insert([
            'jurnal_guru_id' => $journalId,
            'siswa_id' => $studentId,
            'status' => 'alpa',
            'keterangan' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach (['2026-10-08', '2026-10-09'] as $date) {
            $extraJournalId = DB::table('jurnal_gurus')->insertGetId([
                'user_id' => $teacher->id,
                'tanggal' => $date,
                'jam_ke' => '1',
                'kelas_id' => $classId,
                'mata_pelajaran_id' => $subjectId,
                'materi_pembelajaran' => 'Materi',
                'catatan_kegiatan' => null,
                'status_monitoring' => 'verified',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('absensi_siswas')->insert([
                'jurnal_guru_id' => $extraJournalId,
                'siswa_id' => $studentId,
                'status' => 'alpa',
                'keterangan' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $filters = [
            'periode' => 'bulanan',
            'tahun' => 2026,
            'bulan' => 10,
            'kelas_id' => $classId,
        ];
        $response = $this->actingAs($admin)
            ->get(route('admin.absensi.report', $filters))
            ->assertOk()
            ->assertViewHas('summary', fn (array $summary) => $summary['alpa'] === 3
                && $summary['alpa_berulang'] === 1)
            ->assertViewHas('rows', fn ($rows) => $rows->first()->alpa === 3);

        $this->assertEquals(0.0, $response->viewData('rows')->first()->percentage);

        $this->get(route('admin.absensi.report', [
            'periode' => 'semester',
            'tahun' => 2026,
            'semester' => 2,
            'kelas_id' => $classId,
        ]))->assertOk()->assertViewHas('summary', fn (array $summary) => $summary['alpa'] === 3);

        $this->get(route('admin.absensi.report.export', $filters))->assertOk()->assertDownload('laporan-absensi.csv');
    }

    public function test_corrections_to_verified_journals_require_private_supporting_evidence(): void
    {
        [$admin, , , , $studentId, $journalId] = $this->createAttendanceFixture();
        DB::table('jurnal_gurus')->where('id', $journalId)->update(['status_monitoring' => 'verified']);
        $attendanceId = DB::table('absensi_siswas')->insertGetId([
            'jurnal_guru_id' => $journalId,
            'siswa_id' => $studentId,
            'status' => 'alpa',
            'keterangan' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Storage::fake('local');
        $this->actingAs($admin)
            ->from(route('admin.absensi.index'))
            ->put(route('admin.absensi.correct', $attendanceId), [
                'status' => 'izin',
                'alasan' => 'Surat izin telah diperiksa.',
            ])
            ->assertRedirect(route('admin.absensi.index'))
            ->assertSessionHasErrors('bukti');

        $this->put(route('admin.absensi.correct', $attendanceId), [
            'status' => 'izin',
            'alasan' => 'Surat izin telah diperiksa.',
            'bukti' => UploadedFile::fake()->create('surat-izin.pdf', 100, 'application/pdf'),
        ])->assertRedirect();

        $correction = \App\Models\AttendanceCorrection::query()->firstOrFail();
        Storage::disk('local')->assertExists($correction->evidence_path);
        $this->get(route('admin.absensi.evidence', $correction))->assertOk();
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
