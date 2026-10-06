<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
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

                    <section class="mb-4">
                        <div class="d-flex flex-column flex-sm-row justify-content-between gap-1 mb-2">
                            <div>
                                <h2 class="h5 fw-bold mb-1">Absensi siswa</h2>
                                <p class="portal-muted small mb-0">Pilih status setiap siswa. Tidak ada status yang dipilih otomatis.</p>
                            </div>
                            <span class="badge rounded-pill text-bg-light align-self-start">{{ $siswaList->count() }} siswa terdaftar</span>
                        </div>
                        @error('absensi')
                            <div class="alert alert-danger rounded-4 py-2">{{ $message }}</div>
                        @enderror
                        <div class="table-responsive portal-card shadow-none">
                            <table class="table portal-table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th scope="col">Nama siswa</th>
                                        <th scope="col" style="min-width:160px">Kehadiran</th>
                                        <th scope="col" style="min-width:220px">Keterangan (opsional)</th>
                                    </tr>
                                </thead>
                                <tbody id="attendance-roster">
                                    @foreach($siswaList as $siswa)
                                        @php($oldAttendance = old("absensi.{$siswa->id}", []))
                                        <tr data-kelas-id="{{ $siswa->kelas_id }}" hidden>
                                            <td>
                                                <div class="fw-semibold">{{ $siswa->nama_lengkap }}</div>
                                                <div class="portal-muted small">{{ $siswa->kelas->nama_kelas ?? '' }}</div>
                                            </td>
                                            <td>
                                                <label class="visually-hidden" for="attendance-{{ $siswa->id }}">Status {{ $siswa->nama_lengkap }}</label>
                                                <select id="attendance-{{ $siswa->id }}" name="absensi[{{ $siswa->id }}][status]"
                                                        class="form-select portal-form-control @error("absensi.{$siswa->id}.status") is-invalid @enderror"
                                                        disabled>
                                                    <option value="">Pilih status</option>
                                                    <option value="hadir" @selected(($oldAttendance['status'] ?? '') === 'hadir')>Hadir</option>
                                                    <option value="izin" @selected(($oldAttendance['status'] ?? '') === 'izin')>Izin</option>
                                                    <option value="sakit" @selected(($oldAttendance['status'] ?? '') === 'sakit')>Sakit</option>
                                                    <option value="alpa" @selected(($oldAttendance['status'] ?? '') === 'alpa')>Alpa</option>
                                                </select>
                                                @error("absensi.{$siswa->id}.status")<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            </td>
                                            <td>
                                                <label class="visually-hidden" for="attendance-note-{{ $siswa->id }}">Keterangan {{ $siswa->nama_lengkap }}</label>
                                                <input id="attendance-note-{{ $siswa->id }}" type="text"
                                                       name="absensi[{{ $siswa->id }}][keterangan]"
                                                       value="{{ $oldAttendance['keterangan'] ?? '' }}"
                                                       class="form-control portal-form-control" placeholder="Contoh: izin keluarga" disabled>
                                            </td>
                                        </tr>
                                    @endforeach
                                    <tr id="attendance-empty-state">
                                        <td colspan="3" class="text-center portal-muted py-4">
                                            Pilih kelas untuk menampilkan daftar siswanya.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p class="portal-muted small mt-2 mb-0">Pastikan seluruh siswa pada kelas terpilih mendapat status sebelum jurnal dikirim.</p>
                    </section>

                    <div class="d-flex flex-column flex-sm-row justify-content-end gap-2">
                        <a href="{{ route('guru.jurnal.index') }}" class="btn portal-btn-outline rounded-pill px-4 py-2">Batal</a>
                        <button type="submit" class="btn portal-btn-primary rounded-pill px-4 py-2">Kirim jurnal untuk verifikasi</button>
                    </div>
                </form>
            </section>
        </div>
    </main>
    <script>
        (() => {
            const classSelect = document.getElementById('kelas_id');
            const rows = Array.from(document.querySelectorAll('#attendance-roster tr[data-kelas-id]'));
            const emptyState = document.getElementById('attendance-empty-state');
            const updateRoster = () => {
                const selectedClass = classSelect.value;
                const matchingRows = rows.filter((row) => row.dataset.kelasId === selectedClass);

                rows.forEach((row) => {
                    const active = row.dataset.kelasId === selectedClass && selectedClass !== '';
                    row.hidden = !active;
                    row.querySelectorAll('select, input').forEach((field) => {
                        field.disabled = !active;
                        field.required = active && field.tagName === 'SELECT';
                    });
                });

                emptyState.hidden = matchingRows.length > 0;
                emptyState.querySelector('td').textContent = selectedClass
                    ? 'Belum ada siswa terdaftar di kelas ini.'
                    : 'Pilih kelas untuk menampilkan daftar siswanya.';
            };

            classSelect.addEventListener('change', updateRoster);
            updateRoster();
        })();
    </script>
    <x-account-activity-tracker />
</body>
</html>
