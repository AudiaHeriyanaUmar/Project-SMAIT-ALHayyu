<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#104c37">
    <title>Rekap Absensi | SMAIT Al-Hayyu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/portal.css') }}" rel="stylesheet">
</head>
<body class="portal-body">
    <nav class="navbar navbar-expand-lg portal-nav py-3">
        <div class="container">
            <a class="navbar-brand" href="{{ route('admin.dashboard') }}">SMAIT <span class="text-warning">AL-HAYYU</span></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#attendanceNavigation"
                    aria-controls="attendanceNavigation" aria-expanded="false" aria-label="Buka navigasi">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="attendanceNavigation">
                <div class="navbar-nav ms-auto align-items-lg-center gap-lg-3">
                    <a class="nav-link" href="{{ route('admin.dashboard') }}">Monitoring jurnal</a>
                    <a class="nav-link active" href="{{ route('admin.absensi.index') }}">Rekap absensi</a>
                    <a class="nav-link" href="{{ route('admin.accounts.index') }}">Kelola akun</a>
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
                <p class="portal-eyebrow mb-2">Laporan kehadiran</p>
                <h1 class="portal-title h2 mb-2">Rekap absensi siswa</h1>
                <p class="portal-muted mb-0">Pantau kehadiran per kelas, siswa, guru, dan rentang tanggal.</p>
            </div>
            <a href="{{ route('admin.absensi.cetak', $filters) }}" target="_blank" rel="noopener"
               class="btn portal-btn-primary rounded-pill px-4 py-2">Cetak laporan</a>
        </header>

        @if(session('success'))
            <div class="alert alert-success border-0 rounded-4 shadow-sm" role="alert">{{ session('success') }}</div>
        @endif

        <section class="row g-3 mb-4" aria-label="Ringkasan absensi">
            @foreach(['total' => 'Total catatan', 'hadir' => 'Hadir', 'izin' => 'Izin', 'sakit' => 'Sakit', 'alpa' => 'Alpa'] as $key => $label)
                <div class="col-6 col-xl">
                    <article class="portal-stat p-3">
                        <p class="portal-stat-label mb-2">{{ $label }}</p>
                        <p class="portal-stat-value mb-0">{{ number_format($summary[$key]) }}</p>
                    </article>
                </div>
            @endforeach
        </section>

        <section class="portal-card">
            <div class="portal-card-header p-3 p-lg-4">
                <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3">
                    <div>
                        <h2 class="h5 fw-bold mb-1">Data kehadiran</h2>
                        <p class="portal-muted small mb-0">{{ number_format($attendance->total()) }} catatan ditemukan</p>
                    </div>
                    <form action="{{ route('admin.absensi.index') }}" method="GET" class="row g-2">
                        <div class="col-6 col-md-auto">
                            <label for="tanggal_dari" class="form-label small mb-1">Dari tanggal</label>
                            <input id="tanggal_dari" type="date" name="tanggal_dari" value="{{ $filters['tanggal_dari'] ?? '' }}" class="form-control portal-form-control">
                        </div>
                        <div class="col-6 col-md-auto">
                            <label for="tanggal_sampai" class="form-label small mb-1">Sampai tanggal</label>
                            <input id="tanggal_sampai" type="date" name="tanggal_sampai" value="{{ $filters['tanggal_sampai'] ?? '' }}" class="form-control portal-form-control">
                        </div>
                        <div class="col-6 col-md-auto">
                            <label for="kelas_id" class="form-label small mb-1">Kelas</label>
                            <select id="kelas_id" name="kelas_id" class="form-select portal-form-control">
                                <option value="">Semua kelas</option>
                                @foreach($kelasList as $kelas)
                                    <option value="{{ $kelas->id }}" @selected(($filters['kelas_id'] ?? '') == $kelas->id)>{{ $kelas->nama_kelas }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-auto">
                            <label for="siswa_id" class="form-label small mb-1">Siswa</label>
                            <select id="siswa_id" name="siswa_id" class="form-select portal-form-control">
                                <option value="">Semua siswa</option>
                                @foreach($siswaList as $siswa)
                                    <option value="{{ $siswa->id }}" @selected(($filters['siswa_id'] ?? '') == $siswa->id)>
                                        {{ $siswa->nama_lengkap }} · {{ $siswa->nisn }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-auto">
                            <label for="guru_id" class="form-label small mb-1">Guru</label>
                            <select id="guru_id" name="guru_id" class="form-select portal-form-control">
                                <option value="">Semua guru</option>
                                @foreach($guruList as $guru)
                                    <option value="{{ $guru->id }}" @selected(($filters['guru_id'] ?? '') == $guru->id)>{{ $guru->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-auto">
                            <label for="status" class="form-label small mb-1">Status</label>
                            <select id="status" name="status" class="form-select portal-form-control">
                                <option value="">Semua status</option>
                                @foreach(['hadir' => 'Hadir', 'izin' => 'Izin', 'sakit' => 'Sakit', 'alpa' => 'Alpa'] as $value => $label)
                                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 d-flex justify-content-end gap-2">
                            <button type="submit" class="btn portal-btn-primary rounded-pill px-4">Filter</button>
                            <a href="{{ route('admin.absensi.index') }}" class="btn portal-btn-outline rounded-pill px-4">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table portal-table table-hover mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Tanggal / Jam</th>
                            <th scope="col">Siswa</th>
                            <th scope="col">Kelas</th>
                            <th scope="col">Mata pelajaran / guru</th>
                            <th scope="col">Status</th>
                            <th scope="col">Keterangan</th>
                            <th scope="col">Koreksi admin</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($attendance as $record)
                            <tr>
                                <td class="text-nowrap">
                                    {{ \Illuminate\Support\Carbon::parse($record->jurnal->tanggal)->format('d/m/Y') }}
                                    <div class="portal-muted small">Jam {{ $record->jurnal->jam_ke }}</div>
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $record->siswa->nama_lengkap }}</div>
                                    <div class="portal-muted small">{{ $record->siswa->nisn }}</div>
                                </td>
                                <td>{{ $record->jurnal->kelas->nama_kelas ?? '-' }}</td>
                                <td>
                                    <div>{{ $record->jurnal->mataPelajaran->nama_mapel ?? '-' }}</div>
                                    <div class="portal-muted small">{{ $record->jurnal->user->name ?? '-' }}</div>
                                </td>
                                <td><span class="badge rounded-pill {{ $record->status === 'hadir' ? 'text-bg-success' : ($record->status === 'alpa' ? 'text-bg-danger' : 'text-bg-warning') }}">{{ ucfirst($record->status) }}</span></td>
                                <td>{{ $record->keterangan ?: '—' }}</td>
                                <td style="min-width:250px">
                                    <details>
                                        <summary class="small fw-semibold text-success">Koreksi / riwayat ({{ $record->corrections->count() }})</summary>
                                        <form action="{{ route('admin.absensi.correct', $record) }}" method="POST" class="portal-card shadow-none p-3 mt-2">
                                            @csrf
                                            @method('PUT')
                                            <label class="form-label small fw-semibold" for="status-{{ $record->id }}">Status baru</label>
                                            <select id="status-{{ $record->id }}" name="status" class="form-select form-select-sm mb-2" required>
                                                @foreach(['hadir', 'izin', 'sakit', 'alpa'] as $status)
                                                    <option value="{{ $status }}" @selected($record->status === $status)>{{ ucfirst($status) }}</option>
                                                @endforeach
                                            </select>
                                            <label class="form-label small fw-semibold" for="note-{{ $record->id }}">Keterangan</label>
                                            <input id="note-{{ $record->id }}" name="keterangan" value="{{ $record->keterangan }}"
                                                   class="form-control form-control-sm mb-2" maxlength="255">
                                            <label class="form-label small fw-semibold" for="reason-{{ $record->id }}">Alasan koreksi</label>
                                            <textarea id="reason-{{ $record->id }}" name="alasan" class="form-control form-control-sm mb-2" rows="2" maxlength="1000" required></textarea>
                                            <button type="submit" class="btn btn-sm portal-btn-primary rounded-pill px-3">Simpan koreksi</button>
                                        </form>
                                        @foreach($record->corrections as $correction)
                                            <div class="small border-top mt-2 pt-2">
                                                {{ $correction->created_at->format('d/m/Y H:i') }} · {{ $correction->admin->name ?? $correction->admin_name }}<br>
                                                {{ ucfirst($correction->status_sebelumnya) }} → {{ ucfirst($correction->status_baru) }}<br>
                                                <span class="portal-muted">{{ $correction->alasan }}</span>
                                            </div>
                                        @endforeach
                                    </details>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center portal-muted py-5">Tidak ada data absensi untuk filter ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($attendance->hasPages())
                <div class="border-top p-3 p-lg-4">{{ $attendance->links('pagination::bootstrap-5') }}</div>
            @endif
        </section>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <x-account-activity-tracker />
</body>
</html>
