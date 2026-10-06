<?php

namespace App\Http\Controllers;

use App\Models\Pendaftaran;
use App\Models\BroadcastLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http; // Tambahkan ini untuk API Call
use Illuminate\Support\Facades\Log;

class SpmbController extends Controller
{
    public function index()
    {
        return view('spmb.index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'email' => 'required|email',
            'no_whatsapp' => 'required|string|max:20',
            'asal_sekolah' => 'required|string|max:255',
            'jurusan_pilihan' => 'required|string',
        ]);

        $noReg = 'SPMB-ALHAYYU-' . strtoupper(Str::random(6));

        // 1. Simpan data pendaftar ke database
        $pendaftaran = Pendaftaran::create([
            'nomor_pendaftaran' => $noReg,
            'nama_lengkap' => $validated['nama_lengkap'],
            'email' => $validated['email'],
            'no_whatsapp' => $validated['no_whatsapp'],
            'asal_sekolah' => $validated['asal_sekolah'],
            'jurusan_pilihan' => $validated['jurusan_pilihan'],
            'status_pendaftaran' => 'baru'
        ]);

        // 2. Siapkan isi pesan WhatsApp
        $pesan = "Assalamu'alaikum Kak {$pendaftaran->nama_lengkap},\n\n";
        $pesan .= "Terima kasih telah melakukan pendaftaran SPMB di SMAIT Al-Hayyu.\n";
        $pesan .= "Nomor Pendaftaran Anda: *{$noReg}*\n";
        $pesan .= "Jurusan: {$pendaftaran->jurusan_pilihan}\n\n";
        $pesan .= "Silakan simpan nomor pendaftaran ini. Kami akan segera menghubungi Anda untuk informasi seleksi selanjutnya.";

        // 3. Proses Kirim Pesan via Fonnte WA Gateway
        $token = env('FONNTE_WA_TOKEN');
        $statusPesan = 'failed'; // Default status

        // Cek apakah token sudah diisi di .env
        if ($token && $token !== 'your_whatsapp_gateway_token_here') {
            try {
                $response = Http::withoutVerifying()->withHeaders([
                    'Authorization' => $token,
                ])->post('https://api.fonnte.com/send', [
                    'target' => $pendaftaran->no_whatsapp,
                    'message' => $pesan,
                    'countryCode' => '62', // Otomatis mengubah angka 0 di depan jadi 62
                ]);

                // Jika respons dari API sukses
                if ($response->successful()) {
                    $statusPesan = 'sent';
                } else {
                    Log::error('Fonnte API Error: ' . $response->body());
                }
            } catch (\Exception $e) {
                Log::error('WA Gateway Exception: ' . $e->getMessage());
            }
        }

        // 4. Simpan Log Broadcast (berhasil atau gagalnya pesan)
        BroadcastLog::create([
            'pendaftaran_id' => $pendaftaran->id,
            'tipe_pesan' => 'whatsapp',
            'isi_pesan' => $pesan,
            'status' => $statusPesan
        ]);

        return redirect()->back()->with('success', "Pendaftaran Berhasil! Nomor Pendaftaran Anda: {$noReg}. Silakan cek WhatsApp Anda.");
    }
}