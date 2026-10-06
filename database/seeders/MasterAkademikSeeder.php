<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MasterAkademikSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        // 1. Insert Data Mata Pelajaran
        DB::table('mata_pelajarans')->insert([
            ['kode_mapel' => 'MAT', 'nama_mapel' => 'Matematika', 'created_at' => $now, 'updated_at' => $now],
            ['kode_mapel' => 'PAI', 'nama_mapel' => 'Pendidikan Agama Islam', 'created_at' => $now, 'updated_at' => $now],
            ['kode_mapel' => 'BHS', 'nama_mapel' => 'Bahasa Indonesia', 'created_at' => $now, 'updated_at' => $now],
            ['kode_mapel' => 'ENG', 'nama_mapel' => 'Bahasa Inggris', 'created_at' => $now, 'updated_at' => $now],
        ]);

        // 2. Insert Data Kelas (mendapatkan ID otomatis)
        $kelasMipaId = DB::table('kelas')->insertGetId([
            'nama_kelas' => 'X MIPA 1', 
            'created_at' => $now, 
            'updated_at' => $now
        ]);
        
        $kelasIpsId = DB::table('kelas')->insertGetId([
            'nama_kelas' => 'X IPS 1', 
            'created_at' => $now, 
            'updated_at' => $now
        ]);

        // 3. Insert Data Siswa Aktif
        DB::table('siswas')->insert([
            ['nisn' => '0011223341', 'nama_lengkap' => 'Ahmad Fulan', 'kelas_id' => $kelasMipaId, 'created_at' => $now, 'updated_at' => $now],
            ['nisn' => '0011223342', 'nama_lengkap' => 'Siti Aminah', 'kelas_id' => $kelasMipaId, 'created_at' => $now, 'updated_at' => $now],
            ['nisn' => '0011223343', 'nama_lengkap' => 'Budi Santoso', 'kelas_id' => $kelasIpsId, 'created_at' => $now, 'updated_at' => $now],
            ['nisn' => '0011223344', 'nama_lengkap' => 'Dewi Lestari', 'kelas_id' => $kelasIpsId, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }
}