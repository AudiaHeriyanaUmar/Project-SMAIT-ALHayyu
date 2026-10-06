<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserRoleSeeder extends Seeder
{
    public function run(): void
    {
        // Buat Akun Super Admin
        User::create([
            'name' => 'Admin SMAIT',
            'email' => 'admin@smait-alhayyu.sch.id',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        // Buat Akun Guru
        User::create([
            'name' => 'Ustadz Fulan (Guru)',
            'email' => 'guru@smait-alhayyu.sch.id',
            'password' => Hash::make('password123'),
            'role' => 'guru',
        ]);
    }
}