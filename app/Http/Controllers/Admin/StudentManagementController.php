<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Services\AdminAuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentManagementController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'kelas_id' => ['nullable', 'integer', 'exists:kelas,id'],
            'status' => ['nullable', Rule::in(['aktif', 'arsip'])],
        ]);

        $studentsQuery = Siswa::query()
            ->when(($filters['status'] ?? 'aktif') === 'arsip', fn ($query) => $query->onlyTrashed())
            ->when($filters['q'] ?? null, function ($query, $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('nama_lengkap', 'like', "%{$search}%")
                        ->orWhere('nisn', 'like', "%{$search}%");
                });
            })
            ->when($filters['kelas_id'] ?? null, fn ($query, $kelasId) => $query->where('kelas_id', $kelasId));

        return view('admin.students.index', [
            'students' => $studentsQuery->with('kelas')->orderBy('nama_lengkap')->paginate(20)->withQueryString(),
            'filters' => $filters,
            'classes' => Kelas::query()->withCount('siswas')->orderBy('nama_kelas')->get(),
            'activeCount' => Siswa::query()->count(),
            'archivedCount' => Siswa::onlyTrashed()->count(),
        ]);
    }

    public function create(): View
    {
        return view('admin.students.form', [
            'student' => new Siswa,
            'classes' => Kelas::query()->orderBy('nama_kelas')->get(),
            'editing' => false,
        ]);
    }

    public function store(Request $request, AdminAuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validate([
            'nisn' => ['required', 'digits:10', 'unique:siswas,nisn'],
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'kelas_id' => ['required', 'integer', 'exists:kelas,id'],
        ]);

        $student = Siswa::query()->create($validated);
        $auditLogger->record($request->user(), 'student.created', "Siswa {$student->nisn} ditambahkan.", $student, [
            'nisn' => $student->nisn,
            'nama_lengkap' => $student->nama_lengkap,
            'kelas_id' => $student->kelas_id,
        ]);

        return redirect()->route('admin.students.index')->with('success', 'Data siswa berhasil ditambahkan.');
    }

    public function edit(Siswa $student): View
    {
        return view('admin.students.form', [
            'student' => $student,
            'classes' => Kelas::query()->orderBy('nama_kelas')->get(),
            'editing' => true,
        ]);
    }

    public function update(Request $request, Siswa $student, AdminAuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validate([
            'nisn' => ['required', 'digits:10', Rule::unique('siswas', 'nisn')->ignore($student->id)],
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'kelas_id' => ['required', 'integer', 'exists:kelas,id'],
        ]);

        $oldStudent = $student->only(['nisn', 'nama_lengkap', 'kelas_id']);
        $student->update($validated);
        $auditLogger->record($request->user(), 'student.updated', "Data siswa {$student->nisn} diperbarui.", $student, [
            'before' => $oldStudent,
            'after' => $student->only(['nisn', 'nama_lengkap', 'kelas_id']),
        ]);

        return redirect()->route('admin.students.index')->with('success', 'Data siswa berhasil diperbarui.');
    }

    public function archive(Request $request, Siswa $student, AdminAuditLogger $auditLogger): RedirectResponse
    {
        $student->delete();
        $auditLogger->record($request->user(), 'student.archived', "Siswa {$student->nisn} diarsipkan.", $student, [
            'nisn' => $student->nisn,
            'nama_lengkap' => $student->nama_lengkap,
            'kelas_id' => $student->kelas_id,
        ]);

        return redirect()->route('admin.students.index')->with('success', "Siswa {$student->nama_lengkap} diarsipkan. Riwayat absensi tetap tersimpan.");
    }

    public function restore(Request $request, int $studentId, AdminAuditLogger $auditLogger): RedirectResponse
    {
        $student = Siswa::onlyTrashed()->findOrFail($studentId);
        $student->restore();
        $auditLogger->record($request->user(), 'student.restored', "Siswa {$student->nisn} diaktifkan kembali.", $student, [
            'nisn' => $student->nisn,
            'nama_lengkap' => $student->nama_lengkap,
            'kelas_id' => $student->kelas_id,
        ]);

        return redirect()->route('admin.students.index', ['status' => 'arsip'])
            ->with('success', "Siswa {$student->nama_lengkap} diaktifkan kembali.");
    }

    public function import(Request $request): View|RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        [$rows, $errors] = $this->parseImportFile($validated['file']);

        if ($errors !== []) {
            return back()->withErrors(['file' => array_slice($errors, 0, 30)]);
        }

        $token = Str::random(40);
        $request->session()->put('student_import_preview', [
            'token' => $token,
            'expires_at' => now()->addMinutes(15)->timestamp,
            'rows' => $rows,
        ]);

        return view('admin.students.preview', compact('rows', 'token'));
    }

    public function confirmImport(Request $request, AdminAuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'size:40'],
        ]);
        $preview = $request->session()->get('student_import_preview');

        if (
            ! is_array($preview)
            || ! isset($preview['token'], $preview['expires_at'], $preview['rows'])
            || ! hash_equals($preview['token'], $validated['token'])
            || $preview['expires_at'] < now()->timestamp
        ) {
            $request->session()->forget('student_import_preview');

            return redirect()->route('admin.students.index')
                ->withErrors(['file' => 'Pratinjau CSV kedaluwarsa atau tidak valid. Silakan unggah ulang file.']);
        }

        $rows = $preview['rows'];
        $nisnValues = collect($rows)->pluck('nisn');
        $existingNisn = DB::table('siswas')->whereIn('nisn', $nisnValues)->exists();

        if ($existingNisn) {
            $request->session()->forget('student_import_preview');

            return redirect()->route('admin.students.index')
                ->withErrors(['file' => 'NISN dalam pratinjau sudah digunakan. Perbarui data dan unggah ulang CSV.']);
        }

        DB::transaction(function () use ($rows): void {
            foreach ($rows as $index => $row) {
                Siswa::query()->create([
                    'nisn' => $row['nisn'],
                    'nama_lengkap' => $row['nama_lengkap'],
                    'kelas_id' => $row['kelas_id'],
                ]);
            }
        });

        $request->session()->forget('student_import_preview');
        $auditLogger->record($request->user(), 'student.imported', count($rows).' siswa diimpor melalui CSV.', null, [
            'count' => count($rows),
        ]);

        return redirect()->route('admin.students.index')
            ->with('success', count($rows).' siswa berhasil diimpor.');
    }

    /**
     * @return array{0: list<array{nisn: string, nama_lengkap: string, nama_kelas: string, kelas_id: int, line: int}>, 1: list<string>}
     */
    private function parseImportFile(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'rb');

        if ($handle === false) {
            return [[], ['File CSV tidak dapat dibaca.']];
        }

        $header = fgetcsv($handle, null, ',', '"', '\\');
        $errors = [];

        if ($header === false) {
            fclose($handle);

            return [[], ['File CSV kosong atau tidak memiliki header.']];
        }

        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
        $header = array_map(fn ($value) => strtolower(trim((string) $value)), $header);
        $expectedHeaders = ['nisn', 'nama_lengkap', 'nama_kelas'];

        if (count($header) !== count($expectedHeaders) || array_diff($expectedHeaders, $header) !== []) {
            fclose($handle);

            return [[], ['Header CSV harus berisi tepat: nisn,nama_lengkap,nama_kelas.']];
        }

        $headerPositions = array_flip($header);
        $rows = [];
        $line = 1;

        while (($values = fgetcsv($handle, null, ',', '"', '\\')) !== false) {
            $line++;

            if ($values === [null] || (count($values) === 1 && trim((string) $values[0]) === '')) {
                continue;
            }

            if (count($values) !== count($header)) {
                $errors[] = "Baris {$line}: jumlah kolom tidak sesuai header CSV.";
                continue;
            }

            $row = [
                'nisn' => trim((string) $values[$headerPositions['nisn']]),
                'nama_lengkap' => trim((string) $values[$headerPositions['nama_lengkap']]),
                'nama_kelas' => trim((string) $values[$headerPositions['nama_kelas']]),
            ];
            $rowValidator = Validator::make($row, [
                'nisn' => ['required', 'digits:10'],
                'nama_lengkap' => ['required', 'string', 'max:255'],
                'nama_kelas' => ['required', 'string', 'max:255'],
            ]);

            if ($rowValidator->fails()) {
                foreach ($rowValidator->errors()->all() as $error) {
                    $errors[] = "Baris {$line}: {$error}";
                }

                continue;
            }

            $row['line'] = $line;
            $rows[] = $row;

            if (count($rows) > 1000) {
                $errors[] = 'Impor dibatasi maksimal 1.000 siswa per file.';
                break;
            }
        }

        fclose($handle);

        if ($rows === []) {
            $errors[] = 'Tidak ada baris siswa yang dapat diimpor.';
        }

        $nisnCounts = collect($rows)->countBy('nisn');
        $existingNisn = DB::table('siswas')
            ->whereIn('nisn', collect($rows)->pluck('nisn')->unique())
            ->pluck('nisn')
            ->all();
        $classesByName = Kelas::query()
            ->whereIn('nama_kelas', collect($rows)->pluck('nama_kelas')->unique())
            ->get(['id', 'nama_kelas'])
            ->groupBy(fn (Kelas $kelas) => mb_strtolower(trim($kelas->nama_kelas)));

        foreach ($rows as $index => $row) {
            if ($nisnCounts[$row['nisn']] > 1 || in_array($row['nisn'], $existingNisn, true)) {
                $errors[] = "Baris {$row['line']}: NISN {$row['nisn']} sudah pernah digunakan.";
            }

            $matchingClasses = $classesByName->get(mb_strtolower($row['nama_kelas']), collect());

            if ($matchingClasses->count() !== 1) {
                $errors[] = "Baris {$row['line']}: nama kelas \"{$row['nama_kelas']}\" tidak ditemukan atau tidak unik.";
            } else {
                $rows[$index]['kelas_id'] = $matchingClasses->first()->id;
            }
        }

        return [$rows, $errors];
    }

    public function template(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $output = fopen('php://output', 'wb');

            if ($output === false) {
                throw new \RuntimeException('Template CSV tidak dapat dibuat.');
            }

            fputcsv($output, ['nisn', 'nama_lengkap', 'nama_kelas']);
            fclose($output);
        }, 'template-siswa.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
