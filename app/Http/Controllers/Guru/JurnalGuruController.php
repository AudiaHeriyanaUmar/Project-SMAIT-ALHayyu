<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\JurnalGuru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Siswa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class JurnalGuruController extends Controller
{
    public function index()
    {
        $this->ensureGuruAccess();

        $guruId = Auth::id();
        $ringkasanQuery = JurnalGuru::where('user_id', $guruId);
        $ringkasan = [
            'total' => (clone $ringkasanQuery)->count(),
            'pending' => (clone $ringkasanQuery)->where('status_monitoring', 'pending')->count(),
            'verified' => (clone $ringkasanQuery)->where('status_monitoring', 'verified')->count(),
            'bulan_ini' => (clone $ringkasanQuery)
                ->whereMonth('tanggal', now()->month)
                ->whereYear('tanggal', now()->year)
                ->count(),
        ];

        $jurnals = JurnalGuru::with(['kelas', 'mataPelajaran'])
            ->withCount([
                'absensi',
                'absensi as hadir_count' => fn ($query) => $query->where('status', 'hadir'),
                'absensi as izin_count' => fn ($query) => $query->where('status', 'izin'),
                'absensi as sakit_count' => fn ($query) => $query->where('status', 'sakit'),
                'absensi as alpa_count' => fn ($query) => $query->where('status', 'alpa'),
            ])
            ->where('user_id', $guruId)
            ->latest('tanggal')
            ->latest('id')
            ->paginate(10);

        return view('guru.jurnal.index', compact('jurnals', 'ringkasan'));
    }

    public function create()
    {
        $this->ensureGuruAccess();

        $kelasList = Kelas::query()->orderBy('nama_kelas')->get();
        $mapelList = MataPelajaran::query()->orderBy('nama_mapel')->get();
        $siswaList = Siswa::query()->with('kelas')->orderBy('nama_lengkap')->get();

        return view('guru.jurnal.create', compact('kelasList', 'mapelList', 'siswaList'));
    }

    public function store(Request $request)
    {
        $this->ensureGuruAccess();

        $validated = $request->validate([
            'tanggal' => 'required|date',
            'jam_ke' => 'required|string',
            'kelas_id' => 'required|exists:kelas,id',
            'mata_pelajaran_id' => 'required|exists:mata_pelajarans,id',
            'materi_pembelajaran' => 'required|string',
            'catatan_kegiatan' => 'nullable|string',
            'absensi' => ['required', 'array', 'min:1'],
            'absensi.*.status' => ['required', Rule::in(['hadir', 'izin', 'sakit', 'alpa'])],
            'absensi.*.keterangan' => ['nullable', 'string', 'max:255'],
        ]);

        $expectedStudentIds = Siswa::query()
            ->where('kelas_id', $validated['kelas_id'])
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->sort()
            ->values()
            ->all();
        $submittedStudentIds = collect(array_keys($validated['absensi']))
            ->map(fn ($id) => (string) $id)
            ->sort()
            ->values()
            ->all();

        if ($expectedStudentIds === [] || $expectedStudentIds !== $submittedStudentIds) {
            throw ValidationException::withMessages([
                'absensi' => 'Absensi harus diisi untuk seluruh siswa di kelas yang dipilih.',
            ]);
        }

        DB::transaction(function () use ($validated): void {
            $jurnal = JurnalGuru::query()->create([
                'user_id' => Auth::id(),
                'tanggal' => $validated['tanggal'],
                'jam_ke' => $validated['jam_ke'],
                'kelas_id' => $validated['kelas_id'],
                'mata_pelajaran_id' => $validated['mata_pelajaran_id'],
                'materi_pembelajaran' => $validated['materi_pembelajaran'],
                'catatan_kegiatan' => $validated['catatan_kegiatan'] ?? null,
                'status_monitoring' => 'pending',
            ]);

            $jurnal->absensi()->createMany(
                collect($validated['absensi'])->map(fn (array $attendance, string $studentId) => [
                    'siswa_id' => $studentId,
                    'status' => $attendance['status'],
                    'keterangan' => $attendance['keterangan'] ?? null,
                ])->all()
            );
        });

        return redirect()->route('guru.jurnal.index')->with('success', 'Jurnal dan absensi berhasil dikirim untuk ditinjau.');
    }

    private function ensureGuruAccess(): void
    {
        abort_unless(Auth::user()->role === 'guru', 403, 'Akses ditolak.');
    }
}