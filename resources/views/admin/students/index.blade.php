<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#104c37">
    <title>Kelola Siswa | SMAIT Al-Hayyu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/portal.css') }}" rel="stylesheet">
</head>
<body class="portal-body">
    <nav class="navbar navbar-expand-lg portal-nav py-3">
        <div class="container">
            <a class="navbar-brand" href="{{ route('admin.dashboard') }}">SMAIT <span class="text-warning">AL-HAYYU</span></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#studentsNavigation"
                    aria-controls="studentsNavigation" aria-expanded="false" aria-label="Buka navigasi">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="studentsNavigation">
                <div class="navbar-nav ms-auto align-items-lg-center gap-lg-3">
                    <a class="nav-link" href="{{ route('admin.dashboard') }}">Monitoring jurnal</a>
                    <a class="nav-link" href="{{ route('admin.absensi.index') }}">Rekap absensi</a>
                    <a class="nav-link active" href="{{ route('admin.students.index') }}">Kelola siswa</a>
                    <a class="nav-link" href="{{ route('admin.classes.index') }}">Kelola kelas</a>
                    <a class="nav-link" href="{{ route('admin.accounts.index') }}">Kelola akun</a>
                    <a class="nav-link" href="{{ route('admin.audit.index') }}">Audit</a>
                    <a class="nav-link" href="{{ route('admin.backup.index') }}">Backup</a>
                    <form method="POST" action="{{ route('logout') }}" class="ms-lg-2">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-light rounded-pill px-3">Keluar</button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <main class="container portal-main">
        <header class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-end gap-3 mb-4">
            <div>
                <p class="portal-eyebrow mb-2">Data akademik</p>
                <h1 class="portal-title h2 mb-2">Kelola siswa</h1>
                <p class="portal-muted mb-0">Perbarui data siswa.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('admin.students.template') }}" class="btn portal-btn-outline rounded-pill px-3">Unduh template CSV</a>
                <a href="{{ route('admin.students.create') }}" class="btn portal-btn-primary rounded-pill px-3">+ Tambah siswa</a>
            </div>
        </header>

        <div class="alert alert-info border-0 rounded-4 shadow-sm">
            NISN yang pernah dipakai tetap dicadangkan meskipun siswa diarsipkan. Pulihkan data siswa lama untuk mengaktifkannya kembali; jangan membuat identitas duplikat.
        </div>

        @if(session('success'))
            <div class="alert alert-success border-0 rounded-4 shadow-sm" role="alert">{{ session('success') }}</div>
        @endif

        @if($errors->has('file'))
            <div class="alert alert-danger border-0 rounded-4 shadow-sm" role="alert">
                <p class="fw-bold mb-1">CSV belum diimpor. Perbaiki hal berikut:</p>
                <ul class="mb-0">
                    @foreach($errors->get('file') as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="row g-3 mb-4" aria-label="Ringkasan roster siswa">
            <div class="col-6 col-lg-4">
                <article class="portal-stat p-3 p-lg-4">
                    <p class="portal-stat-label mb-3">Siswa aktif</p>
                    <p class="portal-stat-value mb-0">{{ number_format($activeCount) }}</p>
                </article>
            </div>
            <div class="col-6 col-lg-4">
                <article class="portal-stat p-3 p-lg-4">
                    <p class="portal-stat-label mb-3">Siswa diarsipkan</p>
                    <p class="portal-stat-value mb-0">{{ number_format($archivedCount) }}</p>
                </article>
            </div>
            <div class="col-12 col-lg-4">
                <article class="portal-stat p-3 p-lg-4">
                    <p class="portal-stat-label mb-3">Jumlah kelas</p>
                    <p class="portal-stat-value mb-0">{{ number_format($classes->count()) }}</p>
                </article>
            </div>
        </section>

        <section class="portal-card mb-4">
            <div class="portal-card-header p-3 p-lg-4">
                <h2 class="h5 fw-bold mb-1">Impor siswa dari CSV</h2>
                <p class="portal-muted small mb-3">
                    Header wajib: <code>nisn,nama_lengkap,nama_kelas</code>. Nama kelas harus sama dengan nama di sistem.
                    Maksimal 1.000 siswa dan 5 MB per file. Jika satu baris tidak valid, tidak ada baris yang diimpor.
                </p>
                <form action="{{ route('admin.students.import') }}" method="POST" enctype="multipart/form-data"
                      class="d-flex flex-column flex-md-row align-items-md-center gap-2">
                    @csrf
                    <label class="visually-hidden" for="student-csv">Pilih file CSV</label>
                    <input id="student-csv" type="file" name="file" accept=".csv,.txt,text/csv"
                           class="form-control portal-form-control @error('file') is-invalid @enderror" required>
                    <button type="submit" class="btn portal-btn-primary rounded-pill px-4 flex-shrink-0">Pratinjau CSV</button>
                    @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </form>
                <div class="small portal-muted mt-3">
                    Contoh: <code>1234567890,Ahmad Fulan,X MIPA 1</code>
                </div>
            </div>
        </section>

        <section class="portal-card">
            <div class="portal-card-header p-3 p-lg-4">
                <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3">
                    <div>
                        <h2 class="h5 fw-bold mb-1">{{ ($filters['status'] ?? 'aktif') === 'arsip' ? 'Siswa diarsipkan' : 'Roster siswa aktif' }}</h2>
                        <p class="portal-muted small mb-0">{{ number_format($students->total()) }} siswa</p>
                    </div>
                    <form action="{{ route('admin.students.index') }}" method="GET" class="row g-2">
                        <div class="col-8 col-sm-auto">
                            <label for="q" class="visually-hidden">Cari nama atau NISN</label>
                            <input id="q" type="search" name="q" value="{{ $filters['q'] ?? '' }}"
                                   class="form-control portal-form-control" placeholder="Nama atau NISN">
                        </div>
                        <div class="col-4 col-sm-auto">
                            <label for="kelas_id" class="visually-hidden">Filter kelas</label>
                            <select id="kelas_id" name="kelas_id" class="form-select portal-form-control">
                                <option value="">Semua kelas</option>
                                @foreach($classes as $kelas)
                                    <option value="{{ $kelas->id }}" @selected(($filters['kelas_id'] ?? '') == $kelas->id)>
                                        {{ $kelas->nama_kelas }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-8 col-sm-auto">
                            <label for="status" class="visually-hidden">Status siswa</label>
                            <select id="status" name="status" class="form-select portal-form-control">
                                <option value="aktif" @selected(($filters['status'] ?? 'aktif') === 'aktif')>Aktif</option>
                                <option value="arsip" @selected(($filters['status'] ?? '') === 'arsip')>Arsip</option>
                            </select>
                        </div>
                        <div class="col-4 col-sm-auto d-flex gap-2">
                            <button type="submit" class="btn portal-btn-primary rounded-pill px-3">Filter</button>
                            <a href="{{ route('admin.students.index') }}" class="btn portal-btn-outline rounded-pill px-3">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table portal-table table-hover mb-0">
                    <thead>
                        <tr>
                            <th scope="col">NISN</th>
                            <th scope="col">Nama lengkap</th>
                            <th scope="col">Kelas</th>
                            <th scope="col">{{ ($filters['status'] ?? 'aktif') === 'arsip' ? 'Tanggal arsip' : 'Terdaftar' }}</th>
                            <th scope="col">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($students as $student)
                            <tr>
                                <td class="fw-semibold">{{ $student->nisn }}</td>
                                <td>{{ $student->nama_lengkap }}</td>
                                <td>{{ $student->kelas->nama_kelas ?? 'Kelas tidak tersedia' }}</td>
                                <td class="text-nowrap">
                                    @if($student->trashed())
                                        {{ $student->deleted_at->format('d/m/Y') }}
                                    @else
                                        {{ $student->created_at->format('d/m/Y') }}
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-2">
                                        @if($student->trashed())
                                            <form action="{{ route('admin.students.restore', $student->id) }}" method="POST">
                                                @csrf
                                                <button class="btn btn-sm portal-btn-primary rounded-pill px-3" type="submit">Aktifkan</button>
                                            </form>
                                        @else
                                            <a href="{{ route('admin.students.edit', $student) }}" class="btn btn-sm portal-btn-outline rounded-pill px-3">Edit / pindah kelas</a>
                                            <form action="{{ route('admin.students.archive', $student) }}" method="POST"
                                                  onsubmit="return confirm('Arsipkan {{ addslashes($student->nama_lengkap) }}? Riwayat absensinya akan tetap disimpan.')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger rounded-pill px-3" type="submit">Arsipkan</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center portal-muted py-5">
                                    {{ ($filters['status'] ?? 'aktif') === 'arsip' ? 'Belum ada siswa di arsip.' : 'Belum ada siswa aktif di roster.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($students->hasPages())
                <div class="border-top p-3 p-lg-4">{{ $students->links('pagination::bootstrap-5') }}</div>
            @endif
        </section>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <x-account-activity-tracker />
</body>
</html>
