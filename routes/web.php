<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SpmbController;
use App\Http\Controllers\Guru\JurnalGuruController;

// 1. Halaman Utama (Website Sekolah)
Route::get('/', function () {
    return view('landing.index');
})->name('home');

// 2. Halaman SPMB (Bisa diakses publik)
Route::get('/spmb', [SpmbController::class, 'index'])->name('spmb.index');
Route::post('/spmb/daftar', [SpmbController::class, 'store'])->name('spmb.store');

// 3. Dashboard Bawaan Breeze (Setelah Login)
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// 4. Fitur Jurnal Guru (Hanya bisa diakses jika sudah login)
Route::middleware(['auth'])->prefix('guru')->name('guru.')->group(function () {
    Route::get('/jurnal', [JurnalGuruController::class, 'index'])->name('jurnal.index');
    Route::get('/jurnal/tambah', [JurnalGuruController::class, 'create'])->name('jurnal.create');
    Route::post('/jurnal', [JurnalGuruController::class, 'store'])->name('jurnal.store');
});

// 5. Pengaturan Profil Bawaan Breeze
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';