<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SpmbController;
use App\Http\Controllers\Guru\JurnalGuruController;
use App\Http\Controllers\Admin\AdminJurnalController;
use App\Http\Controllers\Admin\AccountManagementController;
use App\Http\Controllers\Admin\AdminAttendanceController;

// 1. Halaman Utama (Website Sekolah)
Route::get('/', function () {
    return view('landing.index');
})->name('home');

// 2. Halaman SPMB (Bisa diakses publik)
Route::get('/spmb', [SpmbController::class, 'index'])->name('spmb.index');
Route::post('/spmb/daftar', [SpmbController::class, 'store'])->name('spmb.store');

// 4. Fitur Jurnal Guru (Hanya bisa diakses jika sudah login)
Route::middleware(['auth', 'role:guru'])->prefix('guru')->name('guru.')->group(function () {
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
    Route::middleware('role:admin')->group(function () {
        Route::get('/dashboard', [AdminJurnalController::class, 'index'])->name('dashboard');
        Route::get('/jurnal', [AdminJurnalController::class, 'index'])->name('jurnal.index');
        Route::post('/jurnal/{id}/verify', [AdminJurnalController::class, 'verify'])->name('jurnal.verify');
        Route::get('/jurnal/cetak', [AdminJurnalController::class, 'cetak'])->name('jurnal.cetak');

        Route::get('/absensi', [AdminAttendanceController::class, 'index'])->name('absensi.index');
        Route::get('/absensi/cetak', [AdminAttendanceController::class, 'cetak'])->name('absensi.cetak');
        Route::put('/absensi/{attendance}/koreksi', [AdminAttendanceController::class, 'correct'])->name('absensi.correct');

        Route::get('/accounts', [AccountManagementController::class, 'index'])->name('accounts.index');
        Route::get('/accounts/create', [AccountManagementController::class, 'create'])->name('accounts.create');
        Route::post('/accounts', [AccountManagementController::class, 'store'])->name('accounts.store');
        Route::get('/accounts/{user}/edit', [AccountManagementController::class, 'edit'])->name('accounts.edit');
        Route::put('/accounts/{user}', [AccountManagementController::class, 'update'])->name('accounts.update');
        Route::get('/accounts/{user}/password', [AccountManagementController::class, 'editPassword'])->name('accounts.password.edit');
        Route::put('/accounts/{user}/password', [AccountManagementController::class, 'updatePassword'])->name('accounts.password.update');
        Route::delete('/accounts/{user}', [AccountManagementController::class, 'destroy'])->name('accounts.destroy');
    });
});

Route::post('/account/activity', fn () => response()->noContent())
    ->middleware('auth')
    ->name('account.activity');

require __DIR__.'/auth.php';