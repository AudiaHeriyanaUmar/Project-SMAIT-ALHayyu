<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\JurnalGuru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
            ->where('user_id', $guruId)
            ->latest('tanggal')
            ->latest('id')
            ->paginate(10);

        return view('guru.jurnal.index', compact('jurnals', 'ringkasan'));
    }

    public function create()
    {
        $this->ensureGuruAccess();

        $kelasList = Kelas::all();
        $mapelList = MataPelajaran::all();
        
        return view('guru.jurnal.create', compact('kelasList', 'mapelList'));
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
        ]);

        JurnalGuru::create([
            'user_id' => Auth::id(),
            'tanggal' => $validated['tanggal'],
            'jam_ke' => $validated['jam_ke'],
            'kelas_id' => $validated['kelas_id'],
            'mata_pelajaran_id' => $validated['mata_pelajaran_id'],
            'materi_pembelajaran' => $validated['materi_pembelajaran'],
            'catatan_kegiatan' => $validated['catatan_kegiatan'] ?? null,
            'status_monitoring' => 'pending'
        ]);

        return redirect()->route('guru.jurnal.index')->with('success', 'Jurnal mengajar berhasil dikirim secara real-time.');
    }

    private function ensureGuruAccess(): void
    {
        abort_unless(Auth::user()->role === 'guru', 403, 'Akses ditolak.');
    }
}