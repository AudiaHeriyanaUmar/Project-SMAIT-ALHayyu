<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AbsensiSiswa;
use App\Models\AttendanceCorrection;
use App\Models\JurnalGuru;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Services\AdminAuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminAttendanceController extends Controller
{
    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'tanggal_dari' => ['nullable', 'date_format:Y-m-d'],
            'tanggal_sampai' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:tanggal_dari'],
            'kelas_id' => ['nullable', 'integer', 'exists:kelas,id'],
            'siswa_id' => ['nullable', 'integer', 'exists:siswas,id'],
            'guru_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['nullable', Rule::in(['hadir', 'izin', 'sakit', 'alpa'])],
        ]);
    }

    public function index(Request $request): View
    {
        $filters = $this->validatedFilters($request);
        $summaryQuery = $this->attendanceQuery($filters, false);

        $summary = [
            'total' => (clone $summaryQuery)->count(),
            'hadir' => (clone $summaryQuery)->where('status', 'hadir')->count(),
            'izin' => (clone $summaryQuery)->where('status', 'izin')->count(),
            'sakit' => (clone $summaryQuery)->where('status', 'sakit')->count(),
            'alpa' => (clone $summaryQuery)->where('status', 'alpa')->count(),
        ];

        $attendance = $this->attendanceQuery($filters, true)
            ->with([
                'siswa.kelas',
                'jurnal.user',
                'jurnal.kelas',
                'jurnal.mataPelajaran',
                'corrections.admin',
            ])
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.absensi.index', [
            'attendance' => $attendance,
            'filters' => $filters,
            'summary' => $summary,
            'kelasList' => Kelas::query()->orderBy('nama_kelas')->get(),
            'siswaList' => Siswa::query()->orderBy('nama_lengkap')->get(),
            'guruList' => DB::table('users')->where('role', 'guru')->whereNull('deleted_at')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function cetak(Request $request, AdminAuditLogger $auditLogger): View
    {
        $filters = $this->validatedFilters($request);
        $auditLogger->record($request->user(), 'attendance.report_printed', 'Rekap absensi dicetak.', null, $filters);
        $attendance = $this->attendanceQuery($filters, true)
            ->with(['siswa.kelas', 'jurnal.user', 'jurnal.kelas', 'jurnal.mataPelajaran'])
            ->orderBy('id')
            ->get();

        return view('admin.absensi.cetak', compact('attendance', 'filters'));
    }

    public function report(Request $request): View
    {
        [$filters, $rows, $summary] = $this->buildAttendanceReport($request);

        return view('admin.absensi.report', [
            'filters' => $filters,
            'rows' => $rows,
            'summary' => $summary,
            'kelasList' => Kelas::query()->orderBy('nama_kelas')->get(),
        ]);
    }

    public function exportReport(Request $request, AdminAuditLogger $auditLogger): StreamedResponse
    {
        [$filters, $rows] = $this->buildAttendanceReport($request);
        $auditLogger->record($request->user(), 'attendance.report_exported', 'Analisis absensi diekspor ke CSV.', null, $filters);

        return response()->streamDownload(function () use ($rows): void {
            $output = fopen('php://output', 'wb');

            if ($output === false) {
                throw new \RuntimeException('Laporan CSV tidak dapat dibuat.');
            }

            fputcsv($output, ['NISN', 'Nama siswa', 'Kelas', 'Total absensi', 'Hadir', 'Izin', 'Sakit', 'Alpa', 'Persentase hadir']);

            foreach ($rows as $row) {
                fputcsv($output, [
                    $row->nisn,
                    $this->safeCsvCell($row->nama_lengkap),
                    $this->safeCsvCell($row->nama_kelas),
                    $row->total,
                    $row->hadir,
                    $row->izin,
                    $row->sakit,
                    $row->alpa,
                    $row->percentage === null ? '' : number_format($row->percentage, 2, '.', ''),
                ]);
            }

            fclose($output);
        }, 'laporan-absensi.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function safeCsvCell(string $value): string
    {
        return preg_match('/^[\t\r\n ]*[=+\-@]/', $value) === 1 ? "'".$value : $value;
    }

    public function correct(Request $request, AbsensiSiswa $attendance, AdminAuditLogger $auditLogger): RedirectResponse
    {
        $attendance->loadMissing('jurnal');
        $validated = $request->validate([
            'status' => ['required', Rule::in(['hadir', 'izin', 'sakit', 'alpa'])],
            'keterangan' => ['nullable', 'string', 'max:255'],
            'alasan' => ['required', 'string', 'max:1000'],
            'bukti' => [
                Rule::requiredIf($attendance->jurnal?->status_monitoring === 'verified'),
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:5120',
            ],
        ]);

        $evidencePath = $request->file('bukti')?->store('attendance-evidence', 'local');

        if ($request->hasFile('bukti') && $evidencePath === false) {
            throw new \RuntimeException('Bukti koreksi tidak dapat disimpan.');
        }

        try {
            DB::transaction(function () use ($request, $attendance, $validated, $evidencePath): void {
                $attendance = AbsensiSiswa::query()->lockForUpdate()->findOrFail($attendance->id);
                $journal = JurnalGuru::query()->lockForUpdate()->findOrFail($attendance->jurnal_guru_id);

                if ($journal->status_monitoring === 'verified' && $evidencePath === null) {
                    throw ValidationException::withMessages([
                        'bukti' => 'Bukti pendukung wajib untuk koreksi setelah jurnal diverifikasi.',
                    ]);
                }

                AttendanceCorrection::query()->create([
                    'absensi_siswa_id' => $attendance->id,
                    'admin_id' => $request->user()->id,
                    'admin_name' => $request->user()->name,
                    'status_sebelumnya' => $attendance->status,
                    'status_baru' => $validated['status'],
                    'keterangan_sebelumnya' => $attendance->keterangan,
                    'keterangan_baru' => $validated['keterangan'] ?? null,
                    'alasan' => $validated['alasan'],
                    'evidence_path' => $evidencePath,
                ]);

                $attendance->update([
                    'status' => $validated['status'],
                    'keterangan' => $validated['keterangan'] ?? null,
                ]);
            });
        } catch (\Throwable $exception) {
            if ($evidencePath !== null) {
                Storage::disk('local')->delete($evidencePath);
            }

            throw $exception;
        }

        $auditLogger->record($request->user(), 'attendance.corrected', "Absensi #{$attendance->id} dikoreksi.", $attendance, [
            'status_baru' => $validated['status'],
            'bukti_terlampir' => $evidencePath !== null,
        ]);

        return back()->with('success', 'Koreksi absensi disimpan dan dicatat.');
    }

    public function evidence(AttendanceCorrection $correction)
    {
        abort_unless($correction->evidence_path, 404);
        abort_unless(Storage::disk('local')->exists($correction->evidence_path), 404, 'Bukti koreksi tidak ditemukan.');

        return Storage::disk('local')->download($correction->evidence_path);
    }

    /**
     * @return array{0: array{periode: string, tahun: int, bulan: int, semester: int, kelas_id: int|null}, 1: \Illuminate\Support\Collection, 2: array<string, int>}
     */
    private function buildAttendanceReport(Request $request): array
    {
        $filters = $request->validate([
            'periode' => ['nullable', Rule::in(['bulanan', 'semester'])],
            'tahun' => ['nullable', 'integer', 'between:2000,2100'],
            'bulan' => ['nullable', 'integer', 'between:1,12'],
            'semester' => ['nullable', 'integer', 'between:1,2'],
            'kelas_id' => ['nullable', 'integer', 'exists:kelas,id'],
        ]);
        $filters = [
            'periode' => $filters['periode'] ?? 'bulanan',
            'tahun' => (int) ($filters['tahun'] ?? now()->year),
            'bulan' => (int) ($filters['bulan'] ?? now()->month),
            'semester' => (int) ($filters['semester'] ?? (now()->month <= 6 ? 1 : 2)),
            'kelas_id' => isset($filters['kelas_id']) ? (int) $filters['kelas_id'] : null,
        ];

        $start = $filters['periode'] === 'bulanan'
            ? now()->setDate($filters['tahun'], $filters['bulan'], 1)->startOfMonth()->toDateString()
            : now()->setDate($filters['tahun'], $filters['semester'] === 1 ? 1 : 7, 1)->startOfMonth()->toDateString();
        $end = $filters['periode'] === 'bulanan'
            ? now()->setDate($filters['tahun'], $filters['bulan'], 1)->endOfMonth()->toDateString()
            : now()->setDate($filters['tahun'], $filters['semester'] === 1 ? 6 : 12, 1)->endOfMonth()->toDateString();

        $rows = DB::table('siswas')
            ->join('kelas', 'kelas.id', '=', 'siswas.kelas_id')
            ->leftJoin('absensi_siswas as a', 'a.siswa_id', '=', 'siswas.id')
            ->leftJoin('jurnal_gurus as j', function (JoinClause $join) use ($start, $end): void {
                $join->on('j.id', '=', 'a.jurnal_guru_id')
                    ->whereBetween('j.tanggal', [$start, $end]);
            })
            ->whereNull('siswas.deleted_at')
            ->when($filters['kelas_id'], fn ($query, $classId) => $query->where('siswas.kelas_id', $classId))
            ->select([
                'siswas.id',
                'siswas.nisn',
                'siswas.nama_lengkap',
                'kelas.nama_kelas',
            ])
            ->selectRaw('COUNT(j.id) as total')
            ->selectRaw("COALESCE(SUM(CASE WHEN j.id IS NOT NULL AND a.status = 'hadir' THEN 1 ELSE 0 END), 0) as hadir")
            ->selectRaw("COALESCE(SUM(CASE WHEN j.id IS NOT NULL AND a.status = 'izin' THEN 1 ELSE 0 END), 0) as izin")
            ->selectRaw("COALESCE(SUM(CASE WHEN j.id IS NOT NULL AND a.status = 'sakit' THEN 1 ELSE 0 END), 0) as sakit")
            ->selectRaw("COALESCE(SUM(CASE WHEN j.id IS NOT NULL AND a.status = 'alpa' THEN 1 ELSE 0 END), 0) as alpa")
            ->groupBy('siswas.id', 'siswas.nisn', 'siswas.nama_lengkap', 'kelas.nama_kelas')
            ->orderBy('kelas.nama_kelas')
            ->orderBy('siswas.nama_lengkap')
            ->get()
            ->map(function ($row) {
                $row->total = (int) $row->total;
                $row->hadir = (int) $row->hadir;
                $row->izin = (int) $row->izin;
                $row->sakit = (int) $row->sakit;
                $row->alpa = (int) $row->alpa;
                $row->percentage = $row->total === 0 ? null : ($row->hadir / $row->total) * 100;

                return $row;
            });

        $summary = [
            'siswa' => $rows->count(),
            'total' => $rows->sum('total'),
            'hadir' => $rows->sum('hadir'),
            'izin' => $rows->sum('izin'),
            'sakit' => $rows->sum('sakit'),
            'alpa' => $rows->sum('alpa'),
            'alpa_berulang' => $rows->where('alpa', '>=', 3)->count(),
        ];

        return [$filters, $rows, $summary];
    }

    private function attendanceQuery(array $filters, bool $includeStatus): Builder
    {
        return AbsensiSiswa::query()
            ->when($includeStatus ? ($filters['status'] ?? null) : null, fn ($query, $status) => $query->where('status', $status))
            ->whereHas('siswa', fn ($query) => $query
                ->when($filters['siswa_id'] ?? null, fn ($query, $siswaId) => $query->whereKey($siswaId)))
            ->whereHas('jurnal', function ($query) use ($filters): void {
                $query
                    ->when($filters['tanggal_dari'] ?? null, fn ($query, $tanggal) => $query->whereDate('tanggal', '>=', $tanggal))
                    ->when($filters['tanggal_sampai'] ?? null, fn ($query, $tanggal) => $query->whereDate('tanggal', '<=', $tanggal))
                    ->when($filters['kelas_id'] ?? null, fn ($query, $kelasId) => $query->where('kelas_id', $kelasId))
                    ->when($filters['guru_id'] ?? null, fn ($query, $guruId) => $query->where('user_id', $guruId));
            });
    }
}
