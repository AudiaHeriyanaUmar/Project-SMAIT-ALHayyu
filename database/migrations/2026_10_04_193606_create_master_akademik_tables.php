<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel Kelas
        Schema::create('kelas', function (Blueprint $table) {
            $table->id();
            $table->string('nama_kelas'); // Contoh: X MIPA 1
            $table->foreignId('wali_kelas_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Tabel Mata Pelajaran
        Schema::create('mata_pelajarans', function (Blueprint $table) {
            $table->id();
            $table->string('kode_mapel')->unique(); // Contoh: MAT-X
            $table->string('nama_mapel');
            $table->timestamps();
        });

        // Tabel Siswa Aktif
        Schema::create('siswas', function (Blueprint $table) {
            $table->id();
            $table->string('nisn')->unique();
            $table->string('nama_lengkap');
            $table->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('siswas');
        Schema::dropIfExists('mata_pelajarans');
        Schema::dropIfExists('kelas');
    }
};