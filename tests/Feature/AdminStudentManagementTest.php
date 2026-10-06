<?php

namespace Tests\Feature;

use App\Models\AbsensiSiswa;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminStudentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admins_can_manage_student_rosters(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);

        $this->actingAs($guru)
            ->get(route('admin.students.index'))
            ->assertForbidden();

        $this->get(route('admin.students.create'))->assertForbidden();
        $this->post(route('admin.students.store'), [])->assertForbidden();
        $this->post(route('admin.students.import'), [])->assertForbidden();
    }

    public function test_admin_can_add_edit_and_move_a_student_between_classes(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $firstClassId = $this->createClass('X IPA 1');
        $secondClassId = $this->createClass('X IPA 2');

        $this->actingAs($admin)
            ->post(route('admin.students.store'), [
                'nisn' => '1234567890',
                'nama_lengkap' => 'Siswa Baru',
                'kelas_id' => $firstClassId,
            ])
            ->assertRedirect(route('admin.students.index'));

        $student = Siswa::query()->where('nisn', '1234567890')->firstOrFail();
        $this->assertSame($firstClassId, $student->kelas_id);

        $this->get(route('admin.students.edit', $student))->assertOk()->assertSee('Ubah data siswa');

        $this->put(route('admin.students.update', $student), [
            'nisn' => $student->nisn,
            'nama_lengkap' => 'Siswa Dipindahkan',
            'kelas_id' => $secondClassId,
        ])->assertRedirect(route('admin.students.index'));

        $this->assertSame($secondClassId, $student->refresh()->kelas_id);
        $this->assertSame('Siswa Dipindahkan', $student->nama_lengkap);
    }

    public function test_admin_roster_page_displays_classes_and_csv_import_controls(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->createClass('X IPA 1');
        Siswa::query()->create([
            'nisn' => '1234567890',
            'nama_lengkap' => 'Siswa Roster',
            'kelas_id' => DB::table('kelas')->value('id'),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.students.index'))
            ->assertOk()
            ->assertSee('Roster siswa aktif')
            ->assertSee('Siswa Roster')
            ->assertSee('X IPA 1')
            ->assertSee('name="file"', false)
            ->assertSee('nisn,nama_lengkap,nama_kelas');
    }

    public function test_archiving_preserves_attendance_history_and_admin_can_restore_student(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $guru = User::factory()->create(['role' => 'guru']);
        $classId = $this->createClass('X IPA 1');
        $subjectId = DB::table('mata_pelajarans')->insertGetId([
            'kode_mapel' => 'MAT-X',
            'nama_mapel' => 'Matematika',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $studentId = DB::table('siswas')->insertGetId([
            'nisn' => '1111111111',
            'nama_lengkap' => 'Siswa Berabsensi',
            'kelas_id' => $classId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $journalId = DB::table('jurnal_gurus')->insertGetId([
            'user_id' => $guru->id,
            'tanggal' => now()->toDateString(),
            'jam_ke' => '1',
            'kelas_id' => $classId,
            'mata_pelajaran_id' => $subjectId,
            'materi_pembelajaran' => 'Materi',
            'catatan_kegiatan' => null,
            'status_monitoring' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $attendanceId = DB::table('absensi_siswas')->insertGetId([
            'jurnal_guru_id' => $journalId,
            'siswa_id' => $studentId,
            'status' => 'hadir',
            'keterangan' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.students.archive', $studentId))
            ->assertRedirect(route('admin.students.index'));

        $this->assertSoftDeleted('siswas', ['id' => $studentId]);
        $this->assertDatabaseHas('absensi_siswas', ['id' => $attendanceId, 'status' => 'hadir']);
        $this->assertSame('Siswa Berabsensi', AbsensiSiswa::findOrFail($attendanceId)->siswa->nama_lengkap);
        $this->assertDatabaseCount('siswas', 1);
        $this->assertSame(0, Siswa::query()->count());

        $this->post(route('admin.students.restore', $studentId))
            ->assertRedirect(route('admin.students.index', ['status' => 'arsip']));

        $this->assertNotSoftDeleted('siswas', ['id' => $studentId]);
    }

    public function test_archived_student_nisn_cannot_be_reused_for_a_new_record(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $classId = $this->createClass('X IPA 1');
        $student = Siswa::query()->create([
            'nisn' => '1234567890',
            'nama_lengkap' => 'Siswa Arsip',
            'kelas_id' => $classId,
        ]);
        $student->delete();

        $this->actingAs($admin)
            ->from(route('admin.students.create'))
            ->post(route('admin.students.store'), [
                'nisn' => '1234567890',
                'nama_lengkap' => 'Duplikat Arsip',
                'kelas_id' => $classId,
            ])
            ->assertRedirect(route('admin.students.create'))
            ->assertSessionHasErrors('nisn');

        $this->assertDatabaseCount('siswas', 1);
    }

    public function test_admin_can_import_csv_when_every_row_is_valid(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->createClass('X IPA 1');

        $preview = $this->actingAs($admin)
            ->post(route('admin.students.import'), [
                'file' => UploadedFile::fake()->createWithContent(
                    'students.csv',
                    "nisn,nama_lengkap,nama_kelas\n1234567890,Ahmad Fulan,X IPA 1\n1234567891,\"Siti, Aminah\",X IPA 1\n",
                ),
            ])
            ->assertOk()
            ->assertViewIs('admin.students.preview')
            ->assertSee('Siti, Aminah');

        $this->assertDatabaseCount('siswas', 0);
        $token = $preview->viewData('token');

        $this->post(route('admin.students.import.confirm'), ['token' => $token])
            ->assertRedirect(route('admin.students.index'))
            ->assertSessionHas('success', '2 siswa berhasil diimpor.');

        $this->assertDatabaseCount('siswas', 2);
        $this->assertDatabaseHas('siswas', [
            'nisn' => '1234567891',
            'nama_lengkap' => 'Siti, Aminah',
        ]);
    }

    public function test_csv_preview_token_can_only_be_used_once_and_cannot_be_forged(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->createClass('X IPA 1');

        $this->actingAs($admin)
            ->post(route('admin.students.import.confirm'), ['token' => str_repeat('a', 40)])
            ->assertRedirect(route('admin.students.index'))
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('siswas', 0);
    }

    public function test_csv_import_is_atomic_when_a_row_has_a_duplicate_nisn_or_unknown_class(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $classId = $this->createClass('X IPA 1');
        Siswa::query()->create([
            'nisn' => '1234567890',
            'nama_lengkap' => 'Existing Student',
            'kelas_id' => $classId,
        ]);

        $this->followingRedirects()
            ->actingAs($admin)
            ->from(route('admin.students.index'))
            ->post(route('admin.students.import'), [
                'file' => UploadedFile::fake()->createWithContent(
                    'students.csv',
                    "nisn,nama_lengkap,nama_kelas\n1234567891,Valid Student,X IPA 1\n1234567890,Duplicate Student,X IPA 1\n1234567892,Unknown Class,IX Z\n",
                ),
            ])
            ->assertOk()
            ->assertSee('Baris 3: NISN 1234567890 sudah pernah digunakan.')
            ->assertSee('Baris 4: nama kelas "IX Z" tidak ditemukan atau tidak unik.');

        $this->assertDatabaseCount('siswas', 1);
        $this->assertDatabaseMissing('siswas', ['nisn' => '1234567891']);
    }

    public function test_csv_import_requires_the_documented_header(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->from(route('admin.students.index'))
            ->post(route('admin.students.import'), [
                'file' => UploadedFile::fake()->createWithContent(
                    'students.csv',
                    "nisn,nama_lengkap,kelas_id\n1234567890,Ahmad,1\n",
                ),
            ])
            ->assertRedirect(route('admin.students.index'))
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('siswas', 0);
    }

    public function test_admin_can_download_the_csv_template(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.students.template'))
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=template-siswa.csv');
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
