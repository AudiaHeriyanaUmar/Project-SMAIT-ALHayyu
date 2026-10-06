<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pendaftarans', function (Blueprint $table) {
            // Relasi ke akun User (agar pendaftar punya dashboard login)
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->cascadeOnDelete();
            
            // Biodata Tambahan
            $table->string('nisn')->nullable()->after('no_whatsapp');
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->enum('jenis_kelamin', ['L', 'P'])->nullable();
            
            // Data Orang Tua
            $table->string('nama_ayah')->nullable();
            $table->string('nama_ibu')->nullable();
            $table->string('no_telp_ortu')->nullable();
            
            // File Uploads (Path)
            $table->string('berkas_kk')->nullable();
            $table->string('berkas_ijazah')->nullable();
            $table->string('berkas_pasfoto')->nullable();
        });
    }

    public function down(): void
    {
        // Logika rollback
    }
};