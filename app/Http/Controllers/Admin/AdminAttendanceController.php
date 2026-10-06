<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AbsensiSiswa;
use App\Models\AttendanceCorrection;
use App\Models\JurnalGuru;
use App\Models\Kelas;
use App\Models\Siswa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

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

    public function cetak(Request $request): View
    {
        $filters = $this->validatedFilters($request);
        $attendance = $this->attendanceQuery($filters, true)
            ->with(['siswa.kelas', 'jurnal.user', 'jurnal.kelas', 'jurnal.mataPelajaran'])
            ->orderBy('id')
            ->get();

        return view('admin.absensi.cetak', compact('attendance', 'filters'));
    }

    public function correct(Request $request, AbsensiSiswa $attendance): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['hadir', 'izin', 'sakit', 'alpa'])],
            'keterangan' => ['nullable', 'string', 'max:255'],
            'alasan' => ['required', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($request, $attendance, $validated): void {
            $attendance = AbsensiSiswa::query()->lockForUpdate()->findOrFail($attendance->id);

            AttendanceCorrection::query()->create([
                'absensi_siswa_id' => $attendance->id,
                'admin_id' => $request->user()->id,
                'admin_name' => $request->user()->name,
                'status_sebelumnya' => $attendance->status,
                'status_baru' => $validated['status'],
                'keterangan_sebelumnya' => $attendance->keterangan,
                'keterangan_baru' => $validated['keterangan'] ?? null,
                'alasan' => $validated['alasan'],
            ]);

            $attendance->update([
                'status' => $validated['status'],
                'keterangan' => $validated['keterangan'] ?? null,
            ]);
        });

        return back()->with('success', 'Koreksi absensi disimpan dan dicatat.');
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
