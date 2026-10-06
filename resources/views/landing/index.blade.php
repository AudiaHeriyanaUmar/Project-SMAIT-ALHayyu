<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMAIT Al-Hayyu - Official Website</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-success py-3 shadow">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">SMAIT AL-HAYYU</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link active" href="{{ route('home') }}">Beranda</a>
                <a class="nav-link text-warning fw-bold" href="{{ route('spmb.index') }}">SPMB Online</a>
                <a class="nav-link" href="{{ route('guru.jurnal.index') }}">Jurnal Guru</a>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div class="p-5 mb-4 bg-white rounded-3 shadow-sm border text-center">
            <h1 class="display-5 fw-bold text-success">Selamat Datang di SMAIT Al-Hayyu</h1>
            <p class="fs-5 text-muted">Media Informasi Digital & Transformatif Pendidikan Islam Terpadu.</p>
            <div class="d-flex justify-content-center gap-3 mt-4">
                <a href="{{ route('spmb.index') }}" class="btn btn-primary btn-lg">Penerimaan Murid Baru (SPMB)</a>
                <a href="{{ route('guru.jurnal.index') }}" class="btn btn-outline-success btn-lg">Akses Jurnal Guru</a>
            </div>
        </div>
    </div>
</body>
</html>
