<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Backup database | SMAIT Al-Hayyu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/portal.css') }}" rel="stylesheet">
</head>
<body class="portal-body">
    <nav class="navbar navbar-expand-lg portal-nav py-3"><div class="container"><a class="navbar-brand" href="{{ route('admin.dashboard') }}">SMAIT <span class="text-warning">AL-HAYYU</span></a></div></nav>
    <main class="container portal-main">
        <header class="mb-4">
            <p class="portal-eyebrow mb-2">Perlindungan data</p>
            <h1 class="portal-title h2 mb-2">Backup database</h1>
            <p class="portal-muted mb-0">Buat salinan SQL lengkap database MySQL untuk disimpan di lokasi aman.</p>
        </header>
        <section class="portal-card p-3 p-lg-4">
            <div class="alert alert-warning border-0 rounded-4">
                Backup berisi data pribadi siswa, akun, dan hash password. Simpan terenkripsi dengan akses terbatas. Pengunduhan tidak memulihkan data secara otomatis.
            </div>
            <a href="{{ route('admin.backup.download') }}" class="btn portal-btn-primary rounded-pill px-4">Unduh backup SQL</a>
            <p class="portal-muted small mt-3 mb-0">Pemulihan dilakukan manual oleh teknisi setelah membuat backup database terkini dan memasuki maintenance mode.</p>
        </section>
    </main>
</body>
</html>
