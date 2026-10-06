<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JurnalGuru;
use Illuminate\Http\Request;

class AdminJurnalController extends Controller
{
    // Halaman Monitoring
    public function index(Request $request)
    {
        // Proteksi agar hanya admin yang bisa akses
        if (auth()->user()->role !== 'admin') abort(403, 'Akses Ditolak');

        $filters = $request->validate([
            'bulan' => ['nullable', 'integer', 'between:1,12'],
            'status' => ['nullable', 'in:pending,verified'],
        ]);

        $summaryQuery = JurnalGuru::query()
            ->when($filters['bulan'] ?? null, fn ($query, $bulan) => $query->whereMonth('tanggal', $bulan));

        $summary = [
            'total' => (clone $summaryQuery)->count(),
            'pending' => (clone $summaryQuery)->where('status_monitoring', 'pending')->count(),
            'verified' => (clone $summaryQuery)->where('status_monitoring', 'verified')->count(),
            'guru' => (clone $summaryQuery)->distinct('user_id')->count('user_id'),
        ];

        $jurnals = JurnalGuru::with(['user', 'kelas', 'mataPelajaran'])
            ->when($filters['bulan'] ?? null, fn ($query, $bulan) => $query->whereMonth('tanggal', $bulan))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status_monitoring', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.jurnal.index', compact('jurnals', 'summary', 'filters'));
    }

    // Fitur Evaluasi (Verifikasi Jurnal)
    public function verify($id)
    {
        if (auth()->user()->role !== 'admin') abort(403);

        $jurnal = JurnalGuru::findOrFail($id);
        $jurnal->update(['status_monitoring' => 'verified']);

        return redirect()->back()->with('success', 'Jurnal mengajar berhasil diverifikasi.');
    }

    // Fitur Pengarsipan (Cetak Laporan)
    public function cetak(Request $request)
    {
        if (auth()->user()->role !== 'admin') abort(403);

        $query = JurnalGuru::with(['user', 'kelas', 'mataPelajaran'])->orderBy('tanggal', 'asc');

        if ($request->filled('bulan')) {
            $query->whereMonth('tanggal', $request->bulan);
            $namaBulan = date('F', mktime(0, 0, 0, $request->bulan, 10));
        } else {
            $namaBulan = 'Semua Bulan';
        }

        $jurnals = $query->get();
        return view('admin.jurnal.cetak', compact('jurnals', 'namaBulan'));
    }
}