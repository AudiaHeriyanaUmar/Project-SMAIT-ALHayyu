<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jurnal_gurus', function (Blueprint $table) {
            // Hapus kolom lama berformat teks
            $table->dropColumn(['kelas', 'mata_pelajaran']);
            
            // Tambahkan relasi ID
            $table->foreignId('kelas_id')->after('jam_ke')->constrained('kelas')->cascadeOnDelete();
            $table->foreignId('mata_pelajaran_id')->after('kelas_id')->constrained('mata_pelajarans')->cascadeOnDelete();
        });

        Schema::table('absensi_siswas', function (Blueprint $table) {
            $table->dropColumn('nama_siswa');
            $table->foreignId('siswa_id')->after('jurnal_guru_id')->constrained('siswas')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        // Logika rollback (mengembalikan kolom lama)
    }
};