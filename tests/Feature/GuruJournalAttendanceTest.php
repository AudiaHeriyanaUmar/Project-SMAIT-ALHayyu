<?php

namespace Tests\Feature;

use App\Models\JurnalGuru;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GuruJournalAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guru_creates_journal_and_attendance_for_every_student_in_selected_class(): void
    {
        [$guru, $classId, $subjectId, $studentIds] = $this->createClassRoster();

        $this->actingAs($guru)
            ->post(route('guru.jurnal.store'), [
                'tanggal' => '2026-10-07',
                'jam_ke' => '1-2',
                'kelas_id' => $classId,
                'mata_pelajaran_id' => $subjectId,
                'materi_pembelajaran' => 'Bilangan pecahan',
                'catatan_kegiatan' => 'Latihan kelompok',
                'absensi' => [
                    $studentIds[0] => ['status' => 'hadir', 'keterangan' => ''],
                    $studentIds[1] => ['status' => 'izin', 'keterangan' => 'Izin keluarga'],
                ],
            ])
            ->assertRedirect(route('guru.jurnal.index'));

        $journal = JurnalGuru::query()->firstOrFail();
        $this->assertSame($guru->id, $journal->user_id);
        $this->assertSame('pending', $journal->status_monitoring);
        $this->assertDatabaseHas('absensi_siswas', [
            'jurnal_guru_id' => $journal->id,
            'siswa_id' => $studentIds[0],
            'status' => 'hadir',
        ]);
        $this->assertDatabaseHas('absensi_siswas', [
            'jurnal_guru_id' => $journal->id,
            'siswa_id' => $studentIds[1],
            'status' => 'izin',
            'keterangan' => 'Izin keluarga',
        ]);
        $this->assertSame(2, $journal->absensi()->count());

        $this->get(route('guru.jurnal.index'))
            ->assertOk()
            ->assertSee('1 hadir')
            ->assertSee('Izin 1');
    }

    public function test_journal_submission_requires_explicit_attendance_for_the_complete_class_roster(): void
    {
        [$guru, $classId, $subjectId, $studentIds] = $this->createClassRoster();

        $this->actingAs($guru)
            ->from(route('guru.jurnal.create'))
            ->post(route('guru.jurnal.store'), [
                'tanggal' => '2026-10-07',
                'jam_ke' => '1-2',
                'kelas_id' => $classId,
                'mata_pelajaran_id' => $subjectId,
                'materi_pembelajaran' => 'Bilangan pecahan',
                'absensi' => [
                    $studentIds[0] => ['status' => 'hadir'],
                ],
            ])
            ->assertSessionHasErrors('absensi')
            ->assertRedirect(route('guru.jurnal.create'));

        $this->assertDatabaseCount('jurnal_gurus', 0);
        $this->assertDatabaseCount('absensi_siswas', 0);
    }

    public function test_attendance_cannot_be_forged_for_a_student_outside_the_selected_class(): void
    {
        [$guru, $classId, $subjectId, $studentIds] = $this->createClassRoster();
        $otherClassId = DB::table('kelas')->insertGetId([
            'nama_kelas' => 'XI IPS 1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $otherStudentId = DB::table('siswas')->insertGetId([
            'nisn' => '9988776655',
            'nama_lengkap' => 'Siswa Luar Kelas',
            'kelas_id' => $otherClassId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($guru)
            ->post(route('guru.jurnal.store'), [
                'tanggal' => '2026-10-07',
                'jam_ke' => '1-2',
                'kelas_id' => $classId,
                'mata_pelajaran_id' => $subjectId,
                'materi_pembelajaran' => 'Bilangan pecahan',
                'absensi' => [
                    $studentIds[0] => ['status' => 'hadir'],
                    $otherStudentId => ['status' => 'hadir'],
                ],
            ])
            ->assertSessionHasErrors('absensi');

        $this->assertDatabaseCount('jurnal_gurus', 0);
    }

    public function test_archived_students_are_not_in_the_roster_for_new_journals(): void
    {
        [$guru, $classId, , $studentIds] = $this->createClassRoster();
        DB::table('siswas')->where('id', $studentIds[1])->update(['deleted_at' => now()]);

        $this->actingAs($guru)
            ->get(route('guru.jurnal.create'))
            ->assertOk()
            ->assertSee('Siswa Satu')
            ->assertDontSee('Siswa Dua')
            ->assertSee('name="absensi['.$studentIds[0].'][status]"', false)
            ->assertDontSee('name="absensi['.$studentIds[1].'][status]"', false);

        $this->assertSame(1, DB::table('siswas')->where('kelas_id', $classId)->whereNull('deleted_at')->count());
    }

    private function createClassRoster(): array
    {
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
        $studentIds = [];

        foreach (['Siswa Satu', 'Siswa Dua'] as $index => $name) {
            $studentIds[] = DB::table('siswas')->insertGetId([
                'nisn' => '123456789'.($index + 1),
                'nama_lengkap' => $name,
                'kelas_id' => $classId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return [$guru, $classId, $subjectId, $studentIds];
    }
}
