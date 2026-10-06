<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absensi_siswas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jurnal_guru_id')->constrained('jurnal_gurus')->onDelete('cascade');
            $table->string('nama_siswa');
            $table->enum('status', ['hadir', 'izin', 'sakit', 'alpa'])->default('hadir');
            $table->string('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensi_siswas');
    }
};
