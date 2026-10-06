<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('absensi_siswas', function (Blueprint $table) {
            $table->unique(['jurnal_guru_id', 'siswa_id'], 'absensi_jurnal_siswa_unique');
        });

        Schema::create('attendance_corrections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('absensi_siswa_id')->constrained('absensi_siswas')->cascadeOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('admin_name');
            $table->string('status_sebelumnya', 20);
            $table->string('status_baru', 20);
            $table->string('keterangan_sebelumnya')->nullable();
            $table->string('keterangan_baru')->nullable();
            $table->text('alasan');
            $table->timestamps();

            $table->index(['admin_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_corrections');

        Schema::table('absensi_siswas', function (Blueprint $table) {
            $table->dropUnique('absensi_jurnal_siswa_unique');
        });
    }
};
