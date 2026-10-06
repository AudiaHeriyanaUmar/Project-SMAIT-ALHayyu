<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#104c37">
    <meta name="description" content="SMAIT Al-Hayyu — informasi sekolah, penerimaan murid baru, dan layanan jurnal guru.">
    <title>SMAIT Al-Hayyu | Pendidikan Islami, Tumbuh Bersama</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/portal.css') }}" rel="stylesheet">
</head>
<body class="portal-body">
    <nav class="navbar navbar-expand-lg portal-nav py-3">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('home') }}">
                <span class="portal-feature-icon" aria-hidden="true">H</span>
                <span>SMAIT <span class="text-warning">AL-HAYYU</span></span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavigation"
                    aria-controls="mainNavigation" aria-expanded="false" aria-label="Buka navigasi">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNavigation">
                <div class="navbar-nav ms-auto align-items-lg-center gap-lg-3">
                    <a class="nav-link active" href="{{ route('home') }}">Beranda</a>
                    <a class="nav-link" href="{{ route('spmb.index') }}">Penerimaan murid</a>
                    @auth
                        <a href="{{ route('dashboard') }}" class="btn btn-sm btn-light rounded-pill px-4 mt-2 mt-lg-0">Dashboard saya</a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-sm btn-outline-light rounded-pill px-4 mt-2 mt-lg-0">Login guru / admin</a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <main>
        <section class="container py-4 py-lg-5">
            <div class="portal-hero p-4 p-md-5">
                <div class="portal-hero-content row align-items-center g-4 py-lg-4">
                    <div class="col-lg-8">
                        <span class="badge rounded-pill text-bg-light text-success px-3 py-2 mb-4">Pendidikan Islam Terpadu</span>
                        <h1 class="display-4 fw-bold mb-3">Mendidik dengan ilmu.<br class="d-none d-md-block"> Menumbuhkan dengan akhlak.</h1>
                        <p class="fs-5 text-white-50 mb-4" style="max-width:620px">
                            Selamat datang di SMAIT Al-Hayyu. Temukan informasi sekolah dan akses layanan pendidikan dalam satu tempat.
                        </p>
                        <div class="d-flex flex-column flex-sm-row gap-2">
                            <a href="{{ route('spmb.index') }}" class="btn btn-light btn-lg rounded-pill px-4 fw-bold text-success">
                                Informasi SPMB <span aria-hidden="true">→</span>
                            </a>
                            @auth
                                <a href="{{ route('dashboard') }}" class="btn btn-outline-light btn-lg rounded-pill px-4">Buka dashboard</a>
                            @else
                                <a href="{{ route('login') }}" class="btn btn-outline-light btn-lg rounded-pill px-4">Login guru / admin</a>
                            @endauth
                        </div>
                    </div>
                    <div class="col-lg-4 d-none d-lg-block">
                        <div class="rounded-5 p-4 p-xl-5 text-center" style="border:1px solid rgba(255,255,255,.2);background:rgba(255,255,255,.08)">
                            <span class="d-block mb-3" style="font-size:4rem;color:#f0cd82" aria-hidden="true">✦</span>
                            <p class="h4 fw-bold mb-2">SMAIT Al-Hayyu</p>
                            <p class="text-white-50 mb-0">Informasi sekolah & layanan akademik digital</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="container py-4 py-lg-5">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-2 mb-4">
                <div>
                    <p class="portal-eyebrow mb-2">Layanan digital</p>
                    <h2 class="portal-title h2 mb-0">Semua kebutuhan, lebih mudah diakses</h2>
                </div>
                <p class="portal-muted mb-0">Pilih layanan yang Anda perlukan.</p>
            </div>
            <div class="row g-3 g-lg-4">
                <div class="col-md-6 col-lg-4">
                    <article class="portal-feature p-4">
                        <span class="portal-feature-icon mb-4" aria-hidden="true">01</span>
                        <h3 class="h5 fw-bold">Penerimaan murid baru</h3>
                        <p class="portal-muted mb-4">Akses formulir pendaftaran SPMB secara online.</p>
                        <a href="{{ route('spmb.index') }}" class="link-success fw-bold text-decoration-none">Buka layanan <span aria-hidden="true">→</span></a>
                    </article>
                </div>
                <div class="col-md-6 col-lg-4">
                    <article class="portal-feature p-4">
                        <span class="portal-feature-icon mb-4" aria-hidden="true">02</span>
                        <h3 class="h5 fw-bold">Jurnal mengajar</h3>
                        <p class="portal-muted mb-4">Guru dapat mencatat kegiatan pembelajaran dan memantau verifikasi.</p>
                        @auth
                            <a href="{{ route('dashboard') }}" class="link-success fw-bold text-decoration-none">Masuk ke dashboard <span aria-hidden="true">→</span></a>
                        @else
                            <a href="{{ route('login') }}" class="link-success fw-bold text-decoration-none">Login untuk melanjutkan <span aria-hidden="true">→</span></a>
                        @endauth
                    </article>
                </div>
                <div class="col-md-6 col-lg-4">
                    <article class="portal-feature p-4">
                        <span class="portal-feature-icon mb-4" aria-hidden="true">03</span>
                        <h3 class="h5 fw-bold">Monitoring akademik</h3>
                        <p class="portal-muted mb-4">Dashboard khusus admin untuk meninjau dan memverifikasi jurnal guru.</p>
                        @auth
                            <a href="{{ route('dashboard') }}" class="link-success fw-bold text-decoration-none">Buka dashboard <span aria-hidden="true">→</span></a>
                        @else
                            <a href="{{ route('login') }}" class="link-success fw-bold text-decoration-none">Login admin <span aria-hidden="true">→</span></a>
                        @endauth
                    </article>
                </div>
            </div>
        </section>
    </main>

    <footer class="portal-footer mt-4 py-4">
        <div class="container d-flex flex-column flex-sm-row justify-content-between gap-2">
            <span class="fw-bold text-dark">SMAIT Al-Hayyu</span>
            <span class="small">Portal informasi dan layanan digital sekolah</span>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @auth
        <x-account-activity-tracker />
    @endauth
</body>
</html>
