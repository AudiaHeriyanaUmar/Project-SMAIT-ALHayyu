<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jurnal_gurus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('tanggal');
            $table->string('jam_ke');
            $table->string('kelas');
            $table->string('mata_pelajaran');
            $table->text('materi_pembelajaran');
            $table->text('catatan_kegiatan')->nullable();
            $table->enum('status_monitoring', ['pending', 'verified'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jurnal_gurus');
    }
};
