<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#104c37">
    <title>Buat Jurnal | SMAIT Al-Hayyu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/portal.css') }}" rel="stylesheet">
</head>
<body class="portal-body">
    <nav class="navbar portal-nav py-3">
        <div class="container">
            <a class="navbar-brand" href="{{ route('guru.jurnal.index') }}">SMAIT <span class="text-warning">AL-HAYYU</span></a>
            <a href="{{ route('guru.jurnal.index') }}" class="btn btn-sm btn-outline-light rounded-pill px-3">Kembali ke jurnal</a>
        </div>
    </nav>

    <main class="container portal-main">
        <div class="mx-auto" style="max-width:800px">
            <div class="mb-4">
                <p class="portal-eyebrow mb-2">Catatan pembelajaran</p>
                <h1 class="portal-title h2 mb-2">Buat jurnal mengajar</h1>
                <p class="portal-muted mb-0">Lengkapi detail kegiatan pembelajaran. Jurnal yang dikirim akan menunggu verifikasi admin.</p>
            </div>

            <section class="portal-card p-3 p-md-4 p-lg-5">
                @if($errors->any())
                    <div class="alert alert-danger rounded-4" role="alert">
                        <p class="fw-bold mb-1">Periksa kembali isian berikut:</p>
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('guru.jurnal.store') }}" method="POST">
                    @csrf
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="tanggal" class="form-label fw-semibold">Tanggal mengajar</label>
                            <input id="tanggal" type="date" name="tanggal" value="{{ old('tanggal', now()->toDateString()) }}"
                                   class="form-control portal-form-control @error('tanggal') is-invalid @enderror" required>
                            @error('tanggal')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="jam_ke" class="form-label fw-semibold">Jam pelajaran</label>
                            <input id="jam_ke" type="text" name="jam_ke" value="{{ old('jam_ke') }}"
                                   class="form-control portal-form-control @error('jam_ke') is-invalid @enderror"
                                   placeholder="Contoh: 1-2" required>
                            @error('jam_ke')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="kelas_id" class="form-label fw-semibold">Kelas</label>
                            <select id="kelas_id" name="kelas_id" class="form-select portal-form-control @error('kelas_id') is-invalid @enderror" required>
                                <option value="">Pilih kelas</option>
                                @foreach($kelasList as $kelas)
                                    <option value="{{ $kelas->id }}" @selected(old('kelas_id') == $kelas->id)>{{ $kelas->nama_kelas }}</option>
                                @endforeach
                            </select>
                            @error('kelas_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="mata_pelajaran_id" class="form-label fw-semibold">Mata pelajaran</label>
                            <select id="mata_pelajaran_id" name="mata_pelajaran_id" class="form-select portal-form-control @error('mata_pelajaran_id') is-invalid @enderror" required>
                                <option value="">Pilih mata pelajaran</option>
                                @foreach($mapelList as $mapel)
                                    <option value="{{ $mapel->id }}" @selected(old('mata_pelajaran_id') == $mapel->id)>{{ $mapel->nama_mapel }}</option>
                                @endforeach
                            </select>
                            @error('mata_pelajaran_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="materi_pembelajaran" class="form-label fw-semibold">Materi pembelajaran</label>
                        <textarea id="materi_pembelajaran" name="materi_pembelajaran" rows="4"
                                  class="form-control portal-form-control @error('materi_pembelajaran') is-invalid @enderror"
                                  placeholder="Tuliskan materi dan pokok pembelajaran..." required>{{ old('materi_pembelajaran') }}</textarea>
                        @error('materi_pembelajaran')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-4">
                        <label for="catatan_kegiatan" class="form-label fw-semibold">Catatan kegiatan <span class="portal-muted fw-normal">(opsional)</span></label>
                        <textarea id="catatan_kegiatan" name="catatan_kegiatan" rows="3"
                                  class="form-control portal-form-control @error('catatan_kegiatan') is-invalid @enderror"
                                  placeholder="Catatan kelas, tindak lanjut, atau informasi lain...">{{ old('catatan_kegiatan') }}</textarea>
                        @error('catatan_kegiatan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="d-flex flex-column flex-sm-row justify-content-end gap-2">
                        <a href="{{ route('guru.jurnal.index') }}" class="btn portal-btn-outline rounded-pill px-4 py-2">Batal</a>
                        <button type="submit" class="btn portal-btn-primary rounded-pill px-4 py-2">Kirim jurnal untuk verifikasi</button>
                    </div>
                </form>
            </section>
        </div>
    </main>
</body>
</html>
