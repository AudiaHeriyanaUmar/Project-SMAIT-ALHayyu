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
        // Mengambil data jurnal beserta relasi kelas dan mata pelajarannya
        $jurnals = JurnalGuru::with(['kelas', 'mataPelajaran'])->where('user_id', Auth::id())->latest()->get();
        return view('guru.jurnal.index', compact('jurnals'));
    }

    public function create()
    {
        $kelasList = Kelas::all();
        $mapelList = MataPelajaran::all();
        
        return view('guru.jurnal.create', compact('kelasList', 'mapelList'));
    }

    public function store(Request $request)
    {
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
}