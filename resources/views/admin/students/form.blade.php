<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#104c37">
    <title>{{ $editing ? 'Edit siswa' : 'Tambah siswa' }} | SMAIT Al-Hayyu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/portal.css') }}" rel="stylesheet">
</head>
<body class="portal-body">
    <nav class="navbar portal-nav py-3">
        <div class="container">
            <a class="navbar-brand" href="{{ route('admin.students.index') }}">SMAIT <span class="text-warning">AL-HAYYU</span></a>
            <a href="{{ route('admin.students.index') }}" class="btn btn-sm btn-outline-light rounded-pill px-3">Kembali ke siswa</a>
        </div>
    </nav>

    <main class="container portal-main">
        <div class="mx-auto" style="max-width:680px">
            <div class="mb-4">
                <p class="portal-eyebrow mb-2">Data akademik</p>
                <h1 class="portal-title h2 mb-2">{{ $editing ? 'Ubah data siswa' : 'Tambah siswa' }}</h1>
                <p class="portal-muted mb-0">Perubahan kelas hanya berlaku pada roster berikutnya; riwayat absensi lama tetap mengikuti kelas saat jurnal dibuat.</p>
            </div>

            <section class="portal-card p-3 p-md-4 p-lg-5">
                <form action="{{ $editing ? route('admin.students.update', $student) : route('admin.students.store') }}" method="POST">
                    @csrf
                    @if($editing) @method('PUT') @endif

                    <div class="mb-3">
                        <label for="nisn" class="form-label fw-semibold">NISN</label>
                        <input id="nisn" name="nisn" value="{{ old('nisn', $student->nisn) }}" inputmode="numeric"
                               minlength="10" maxlength="10" pattern="[0-9]{10}"
                               class="form-control portal-form-control @error('nisn') is-invalid @enderror" required>
                        @error('nisn')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="nama_lengkap" class="form-label fw-semibold">Nama lengkap</label>
                        <input id="nama_lengkap" name="nama_lengkap" value="{{ old('nama_lengkap', $student->nama_lengkap) }}"
                               class="form-control portal-form-control @error('nama_lengkap') is-invalid @enderror" required>
                        @error('nama_lengkap')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-4">
                        <label for="kelas_id" class="form-label fw-semibold">Kelas</label>
                        <select id="kelas_id" name="kelas_id" class="form-select portal-form-control @error('kelas_id') is-invalid @enderror" required>
                            <option value="">Pilih kelas</option>
                            @foreach($classes as $kelas)
                                <option value="{{ $kelas->id }}" @selected(old('kelas_id', $student->kelas_id) == $kelas->id)>
                                    {{ $kelas->nama_kelas }}
                                </option>
                            @endforeach
                        </select>
                        @error('kelas_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="d-flex flex-column flex-sm-row justify-content-end gap-2">
                        <a href="{{ route('admin.students.index') }}" class="btn portal-btn-outline rounded-pill px-4 py-2">Batal</a>
                        <button type="submit" class="btn portal-btn-primary rounded-pill px-4 py-2">
                            {{ $editing ? 'Simpan perubahan' : 'Tambah ke roster' }}
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </main>
    <x-account-activity-tracker />
</body>
</html>
