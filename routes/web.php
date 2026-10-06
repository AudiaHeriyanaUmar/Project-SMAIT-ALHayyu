<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SpmbController;
use App\Http\Controllers\Guru\JurnalGuruController;
use App\Http\Controllers\Admin\AdminJurnalController;

// 1. Halaman Utama (Website Sekolah)
Route::get('/', function () {
    return view('landing.index');
})->name('home');

// 2. Halaman SPMB (Bisa diakses publik)
Route::get('/spmb', [SpmbController::class, 'index'])->name('spmb.index');
Route::post('/spmb/daftar', [SpmbController::class, 'store'])->name('spmb.store');

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

// Arahkan pengguna berdasarkan peran setelah login.
Route::get('/dashboard', function () {
    $role = auth()->user()->role;

    if ($role === 'guru') {
        return redirect()->route('guru.jurnal.index');
    }

    if ($role === 'admin') {
        return redirect()->route('admin.dashboard');
    }

    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Rute Monitoring Admin / Kepala Sekolah
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminJurnalController::class, 'index'])->name('dashboard');
    Route::get('/jurnal', [AdminJurnalController::class, 'index'])->name('jurnal.index');
    Route::post('/jurnal/{id}/verify', [AdminJurnalController::class, 'verify'])->name('jurnal.verify');
    Route::get('/jurnal/cetak', [AdminJurnalController::class, 'cetak'])->name('jurnal.cetak');
});

require __DIR__.'/auth.php';