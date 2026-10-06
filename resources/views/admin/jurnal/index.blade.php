<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#104c37">
    <title>Dashboard Monitoring | SMAIT Al-Hayyu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/portal.css') }}" rel="stylesheet">
</head>
<body class="portal-body">
    @php
        $namaBulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
    @endphp

    <nav class="navbar navbar-expand-lg portal-nav py-3">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('admin.dashboard') }}">
                <span class="portal-feature-icon" aria-hidden="true">A</span>
                <span>SMAIT <span class="text-warning">AL-HAYYU</span></span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavigation"
                    aria-controls="adminNavigation" aria-expanded="false" aria-label="Buka navigasi">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="adminNavigation">
                <div class="navbar-nav ms-auto align-items-lg-center gap-lg-3">
                    <a class="nav-link active" href="{{ route('admin.dashboard') }}">Monitoring jurnal</a>
                    <a class="nav-link" href="{{ route('admin.absensi.index') }}">Rekap absensi</a>
                    <a class="nav-link" href="{{ route('admin.accounts.index') }}">Kelola akun</a>
                    <span class="nav-link d-none d-lg-inline">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}" class="ms-lg-2">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-light rounded-pill px-3">Keluar</button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <main class="container portal-main">
        <header class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-4">
            <div>
                <p class="portal-eyebrow mb-2">Panel administrator</p>
                <h1 class="portal-title h2 mb-2">Monitoring jurnal guru</h1>
                <p class="portal-muted mb-0">Ringkasan aktivitas mengajar dan tindak lanjut verifikasi.</p>
            </div>
            <a href="{{ route('admin.jurnal.cetak', ['bulan' => $filters['bulan'] ?? null]) }}"
               target="_blank" rel="noopener" class="btn portal-btn-primary rounded-pill px-4 py-2">
                Cetak laporan
            </a>
        </header>

        @if(session('success'))
            <div class="alert alert-success border-0 rounded-4 shadow-sm" role="alert">{{ session('success') }}</div>
        @endif

        <section class="row g-3 mb-4" aria-label="Ringkasan jurnal">
            <div class="col-6 col-xl-3">
                <article class="portal-stat p-3 p-lg-4">
                    <p class="portal-stat-label mb-3">Total jurnal</p>
                    <p class="portal-stat-value mb-0">{{ number_format($summary['total']) }}</p>
                </article>
            </div>
            <div class="col-6 col-xl-3">
                <article class="portal-stat p-3 p-lg-4">
                    <p class="portal-stat-label mb-3">Menunggu verifikasi</p>
                    <p class="portal-stat-value mb-0" style="color:#a36a09">{{ number_format($summary['pending']) }}</p>
                </article>
            </div>
            <div class="col-6 col-xl-3">
                <article class="portal-stat p-3 p-lg-4">
                    <p class="portal-stat-label mb-3">Terverifikasi</p>
                    <p class="portal-stat-value mb-0" style="color:#176b4b">{{ number_format($summary['verified']) }}</p>
                </article>
            </div>
            <div class="col-6 col-xl-3">
                <article class="portal-stat p-3 p-lg-4">
                    <p class="portal-stat-label mb-3">Guru aktif mengirim</p>
                    <p class="portal-stat-value mb-0">{{ number_format($summary['guru']) }}</p>
                </article>
            </div>
        </section>

        <section class="portal-card">
            <div class="portal-card-header p-3 p-lg-4">
                <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3">
                    <div>
                        <h2 class="h5 fw-bold mb-1">Jurnal mengajar</h2>
                        <p class="portal-muted small mb-0">{{ number_format($jurnals->total()) }} catatan ditemukan</p>
                    </div>
                    <form action="{{ route('admin.dashboard') }}" method="GET" class="row g-2 align-items-center">
                        <div class="col-6 col-sm-auto">
                            <label for="bulan" class="visually-hidden">Filter bulan</label>
                            <select name="bulan" id="bulan" class="form-select portal-form-control">
                                <option value="">Semua bulan</option>
                                @foreach($namaBulan as $angka => $nama)
                                    <option value="{{ $angka }}" {{ ($filters['bulan'] ?? '') == $angka ? 'selected' : '' }}>
                                        {{ $nama }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-sm-auto">
                            <label for="status" class="visually-hidden">Filter status</label>
                            <select name="status" id="status" class="form-select portal-form-control">
                                <option value="">Semua status</option>
                                <option value="pending" {{ ($filters['status'] ?? '') === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="verified" {{ ($filters['status'] ?? '') === 'verified' ? 'selected' : '' }}>Terverifikasi</option>
                            </select>
                        </div>
                        <div class="col-12 col-sm-auto d-flex gap-2">
                            <button type="submit" class="btn portal-btn-primary rounded-pill px-3 flex-grow-1">Terapkan</button>
                            <a href="{{ route('admin.dashboard') }}" class="btn portal-btn-outline rounded-pill px-3">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table portal-table table-hover mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Tanggal</th>
                            <th scope="col">Guru</th>
                            <th scope="col">Kelas</th>
                            <th scope="col">Mata pelajaran</th>
                            <th scope="col">Materi</th>
                            <th scope="col">Status</th>
                            <th scope="col">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($jurnals as $jurnal)
                            <tr>
                                <td class="text-nowrap">{{ \Illuminate\Support\Carbon::parse($jurnal->tanggal)->format('d/m/Y') }}</td>
                                <td class="fw-semibold">{{ $jurnal->user->name ?? '-' }}</td>
                                <td>{{ $jurnal->kelas->nama_kelas ?? '-' }}</td>
                                <td>{{ $jurnal->mataPelajaran->nama_mapel ?? '-' }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($jurnal->materi_pembelajaran, 45) }}</td>
                                <td>
                                    @if($jurnal->status_monitoring === 'verified')
                                        <span class="badge rounded-pill text-bg-success">Terverifikasi</span>
                                    @else
                                        <span class="badge rounded-pill text-bg-warning">Menunggu</span>
                                    @endif
                                </td>
                                <td>
                                    @if($jurnal->status_monitoring === 'pending')
                                        <form action="{{ route('admin.jurnal.verify', $jurnal->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="btn btn-sm portal-btn-primary rounded-pill px-3">Verifikasi</button>
                                        </form>
                                    @else
                                        <span class="portal-muted small">Selesai</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center portal-muted py-5">Belum ada jurnal untuk filter ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($jurnals->hasPages())
                <div class="border-top p-3 p-lg-4">{{ $jurnals->links('pagination::bootstrap-5') }}</div>
            @endif
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <x-account-activity-tracker />
</body>
</html>
