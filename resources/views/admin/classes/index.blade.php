<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kelola kelas | SMAIT Al-Hayyu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/portal.css') }}" rel="stylesheet">
</head>
<body class="portal-body">
    <nav class="navbar navbar-expand-lg portal-nav py-3">
        <div class="container">
            <a class="navbar-brand" href="{{ route('admin.dashboard') }}">SMAIT <span class="text-warning">AL-HAYYU</span></a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="{{ route('admin.students.index') }}">Kelola siswa</a>
                <a class="nav-link" href="{{ route('admin.accounts.index') }}">Kelola akun</a>
            </div>
        </div>
    </nav>
    <main class="container portal-main">
        <header class="mb-4">
            <p class="portal-eyebrow mb-2">Data akademik</p>
            <h1 class="portal-title h2 mb-2">Kelola kelas</h1>
            <p class="portal-muted mb-0">Kelas yang sudah dipakai jurnal tidak dapat dihapus atau diganti nama agar data historis tetap terbaca.</p>
        </header>
        @if(session('success'))
            <div class="alert alert-success border-0 rounded-4 shadow-sm">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger border-0 rounded-4 shadow-sm"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        <div class="row g-4">
            <div class="col-lg-5">
                <section class="portal-card p-3 p-lg-4 mb-4">
                    <h2 class="h5 fw-bold">Tambah kelas</h2>
                    <form method="POST" action="{{ route('admin.classes.store') }}" class="mt-3">
                        @csrf
                        <label class="form-label" for="new-name">Nama kelas</label>
                        <input id="new-name" name="nama_kelas" value="{{ old('nama_kelas') }}" class="form-control portal-form-control mb-3" required maxlength="255">
                        <label class="form-label" for="new-teacher">Wali kelas (opsional)</label>
                        <select id="new-teacher" name="wali_kelas_id" class="form-select portal-form-control mb-3">
                            <option value="">Belum ditentukan</option>
                            @foreach($teachers as $teacher)<option value="{{ $teacher->id }}">{{ $teacher->name }}</option>@endforeach
                        </select>
                        <button class="btn portal-btn-primary rounded-pill px-4">Tambah kelas</button>
                    </form>
                </section>
                <section class="portal-card p-3 p-lg-4">
                    <h2 class="h5 fw-bold">Kenaikan / pemindahan kelas</h2>
                    <p class="portal-muted small">Seluruh siswa aktif pada kelas asal dipindahkan. Tujuan wajib kosong.</p>
                    <form method="POST" action="{{ route('admin.classes.promote') }}">
                        @csrf
                        <label class="form-label" for="source-class">Kelas asal</label>
                        <select id="source-class" name="source_class_id" class="form-select portal-form-control mb-3" required>
                            <option value="">Pilih kelas</option>
                            @foreach($classes as $class)<option value="{{ $class->id }}">{{ $class->nama_kelas }} ({{ $class->siswas_count }} siswa)</option>@endforeach
                        </select>
                        <label class="form-label" for="target-class">Kelas tujuan kosong</label>
                        <select id="target-class" name="target_class_id" class="form-select portal-form-control mb-3" required>
                            <option value="">Pilih kelas</option>
                            @foreach($classes->where('siswas_count', 0) as $class)<option value="{{ $class->id }}">{{ $class->nama_kelas }}</option>@endforeach
                        </select>
                        <button class="btn portal-btn-primary rounded-pill px-4" onclick="return confirm('Pindahkan seluruh siswa aktif ke kelas tujuan?')">Pindahkan siswa</button>
                    </form>
                </section>
            </div>
            <div class="col-lg-7">
                <section class="portal-card">
                    <div class="table-responsive">
                        <table class="table portal-table mb-0">
                            <thead><tr><th>Kelas</th><th>Siswa aktif</th><th>Wali kelas</th><th>Perbarui wali / nama</th></tr></thead>
                            <tbody>
                                @forelse($classes as $class)
                                    <tr>
                                        <td class="fw-semibold">{{ $class->nama_kelas }}</td><td>{{ $class->siswas_count }}</td><td>{{ $class->waliKelas->name ?? '—' }}</td>
                                        <td>
                                            <form method="POST" action="{{ route('admin.classes.update', $class) }}" class="d-flex flex-column gap-2">
                                                @csrf @method('PUT')
                                                <input name="nama_kelas" value="{{ $class->nama_kelas }}" class="form-control form-control-sm" required>
                                                <select name="wali_kelas_id" class="form-select form-select-sm">
                                                    <option value="">Belum ditentukan</option>
                                                    @foreach($teachers as $teacher)<option value="{{ $teacher->id }}" @selected($class->wali_kelas_id === $teacher->id)>{{ $teacher->name }}</option>@endforeach
                                                </select>
                                                <button class="btn btn-sm portal-btn-outline rounded-pill">Simpan</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center portal-muted py-4">Belum ada kelas.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </main>
</body>
</html>
