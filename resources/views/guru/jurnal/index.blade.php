<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#104c37">
    <title>Jurnal Guru | SMAIT Al-Hayyu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/portal.css') }}" rel="stylesheet">
</head>
<body class="portal-body">
    <nav class="navbar navbar-expand-lg portal-nav py-3">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('guru.jurnal.index') }}">
                <span class="portal-feature-icon" aria-hidden="true">G</span>
                <span>SMAIT <span class="text-warning">AL-HAYYU</span></span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#guruNavigation"
                    aria-controls="guruNavigation" aria-expanded="false" aria-label="Buka navigasi">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="guruNavigation">
                <div class="navbar-nav ms-auto align-items-lg-center gap-lg-3">
                    <a class="nav-link active" href="{{ route('guru.jurnal.index') }}">Jurnal saya</a>
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
        @if(session('success'))
            <div class="alert alert-success border-0 rounded-4 shadow-sm" role="alert">{{ session('success') }}</div>
        @endif

        <section class="portal-hero p-4 p-lg-5 mb-4">
            <div class="portal-hero-content d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-4">
                <div>
                    <p class="small fw-bold text-uppercase mb-2" style="color:#f0cd82;letter-spacing:.13em">Ruang kerja guru</p>
                    <h1 class="display-6 fw-bold mb-2">Assalamu'alaikum, {{ auth()->user()->name }}</h1>
                    <p class="mb-0 text-white-50">Kelola jurnal mengajar dan pantau status verifikasi Anda.</p>
                </div>
                <a href="{{ route('guru.jurnal.create') }}" class="btn btn-light rounded-pill px-4 py-3 fw-bold text-success flex-shrink-0">
                    + Buat jurnal baru
                </a>
            </div>
        </section>

        <section class="row g-3 mb-4" aria-label="Ringkasan jurnal pribadi">
            <div class="col-6 col-xl-3">
                <article class="portal-stat p-3 p-lg-4">
                    <p class="portal-stat-label mb-3">Total jurnal</p>
                    <p class="portal-stat-value mb-0">{{ number_format($ringkasan['total']) }}</p>
                </article>
            </div>
            <div class="col-6 col-xl-3">
                <article class="portal-stat p-3 p-lg-4">
                    <p class="portal-stat-label mb-3">Bulan ini</p>
                    <p class="portal-stat-value mb-0">{{ number_format($ringkasan['bulan_ini']) }}</p>
                </article>
            </div>
            <div class="col-6 col-xl-3">
                <article class="portal-stat p-3 p-lg-4">
                    <p class="portal-stat-label mb-3">Menunggu verifikasi</p>
                    <p class="portal-stat-value mb-0" style="color:#a36a09">{{ number_format($ringkasan['pending']) }}</p>
                </article>
            </div>
            <div class="col-6 col-xl-3">
                <article class="portal-stat p-3 p-lg-4">
                    <p class="portal-stat-label mb-3">Terverifikasi</p>
                    <p class="portal-stat-value mb-0" style="color:#176b4b">{{ number_format($ringkasan['verified']) }}</p>
                </article>
            </div>
        </section>

        <section class="portal-card">
            <div class="portal-card-header d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 p-3 p-lg-4">
                <div>
                    <h2 class="h5 fw-bold mb-1">Riwayat mengajar</h2>
                    <p class="portal-muted small mb-0">Jurnal terbaru ditampilkan lebih dahulu.</p>
                </div>
                <span class="badge rounded-pill text-bg-light px-3 py-2">{{ number_format($jurnals->total()) }} jurnal</span>
            </div>
            <div class="table-responsive">
                <table class="table portal-table table-hover mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Tanggal</th>
                            <th scope="col">Jam</th>
                            <th scope="col">Kelas</th>
                            <th scope="col">Mata pelajaran</th>
                            <th scope="col">Materi</th>
                            <th scope="col">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($jurnals as $jurnal)
                            <tr>
                                <td class="text-nowrap">{{ \Illuminate\Support\Carbon::parse($jurnal->tanggal)->format('d/m/Y') }}</td>
                                <td>{{ $jurnal->jam_ke }}</td>
                                <td>{{ $jurnal->kelas->nama_kelas ?? 'Data tidak tersedia' }}</td>
                                <td>{{ $jurnal->mataPelajaran->nama_mapel ?? 'Data tidak tersedia' }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($jurnal->materi_pembelajaran, 55) }}</td>
                                <td>
                                    @if($jurnal->status_monitoring === 'verified')
                                        <span class="badge rounded-pill text-bg-success">Terverifikasi</span>
                                    @else
                                        <span class="badge rounded-pill text-bg-warning">Menunggu</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <p class="fw-bold mb-1">Belum ada jurnal mengajar</p>
                                    <p class="portal-muted small mb-3">Mulai dengan mencatat kegiatan pembelajaran Anda.</p>
                                    <a href="{{ route('guru.jurnal.create') }}" class="btn portal-btn-primary rounded-pill px-4">Buat jurnal pertama</a>
                                </td>
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
</body>
</html>
