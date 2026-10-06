<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Analisis absensi | SMAIT Al-Hayyu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/portal.css') }}" rel="stylesheet">
</head>
<body class="portal-body">
    <nav class="navbar navbar-expand-lg portal-nav py-3">
        <div class="container">
            <a class="navbar-brand" href="{{ route('admin.dashboard') }}">SMAIT <span class="text-warning">AL-HAYYU</span></a>
            <div class="navbar-nav ms-auto"><a class="nav-link" href="{{ route('admin.absensi.index') }}">Kembali ke rekap</a></div>
        </div>
    </nav>
    <main class="container portal-main">
        <header class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-4">
            <div>
                <p class="portal-eyebrow mb-2">Ringkasan periode</p>
                <h1 class="portal-title h2 mb-2">Analisis kehadiran</h1>
                <p class="portal-muted mb-0">Persentase dihitung dari catatan kehadiran yang tersedia; alpa berulang berarti minimal 3 kali dalam periode.</p>
            </div>
            <a href="{{ route('admin.absensi.report.export', request()->query()) }}" class="btn portal-btn-primary rounded-pill px-4">Ekspor CSV</a>
        </header>

        <section class="portal-card mb-4 p-3 p-lg-4">
            <form action="{{ route('admin.absensi.report') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-6 col-md-2">
                    <label for="periode" class="form-label small">Periode</label>
                    <select id="periode" name="periode" class="form-select portal-form-control">
                        <option value="bulanan" @selected($filters['periode'] === 'bulanan')>Bulanan</option>
                        <option value="semester" @selected($filters['periode'] === 'semester')>Semester</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label for="tahun" class="form-label small">Tahun</label>
                    <input id="tahun" name="tahun" type="number" min="2000" max="2100" value="{{ $filters['tahun'] }}" class="form-control portal-form-control">
                </div>
                <div class="col-6 col-md-2">
                    <label for="bulan" class="form-label small">Bulan</label>
                    <select id="bulan" name="bulan" class="form-select portal-form-control">
                        @foreach(range(1, 12) as $month)
                            <option value="{{ $month }}" @selected($filters['bulan'] === $month)>{{ \Illuminate\Support\Carbon::create()->month($month)->translatedFormat('F') }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label for="semester" class="form-label small">Semester</label>
                    <select id="semester" name="semester" class="form-select portal-form-control">
                        <option value="1" @selected($filters['semester'] === 1)>Semester 1 (Jan–Jun)</option>
                        <option value="2" @selected($filters['semester'] === 2)>Semester 2 (Jul–Des)</option>
                    </select>
                </div>
                <div class="col-8 col-md-3">
                    <label for="kelas_id" class="form-label small">Kelas</label>
                    <select id="kelas_id" name="kelas_id" class="form-select portal-form-control">
                        <option value="">Semua kelas</option>
                        @foreach($kelasList as $kelas)
                            <option value="{{ $kelas->id }}" @selected($filters['kelas_id'] === $kelas->id)>{{ $kelas->nama_kelas }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-4 col-md-1">
                    <button type="submit" class="btn portal-btn-primary rounded-pill px-3">Terapkan</button>
                </div>
            </form>
        </section>

        <section class="row g-3 mb-4" aria-label="Ringkasan periode">
            @foreach(['siswa' => 'Siswa', 'total' => 'Total catatan', 'hadir' => 'Hadir', 'alpa' => 'Alpa', 'alpa_berulang' => 'Alpa ≥ 3 kali'] as $key => $label)
                <div class="col-6 col-xl">
                    <article class="portal-stat p-3">
                        <p class="portal-stat-label mb-2">{{ $label }}</p>
                        <p class="portal-stat-value mb-0">{{ number_format($summary[$key]) }}</p>
                    </article>
                </div>
            @endforeach
        </section>

        <section class="portal-card">
            <div class="table-responsive">
                <table class="table portal-table table-hover mb-0">
                    <thead><tr><th>Siswa</th><th>Kelas</th><th>Total</th><th>Hadir</th><th>Izin</th><th>Sakit</th><th>Alpa</th><th>Persentase hadir</th></tr></thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr>
                                <td><div class="fw-semibold">{{ $row->nama_lengkap }}</div><div class="portal-muted small">{{ $row->nisn }}</div></td>
                                <td>{{ $row->nama_kelas }}</td><td>{{ $row->total }}</td><td>{{ $row->hadir }}</td>
                                <td>{{ $row->izin }}</td><td>{{ $row->sakit }}</td>
                                <td><span class="{{ $row->alpa >= 3 ? 'text-danger fw-bold' : '' }}">{{ $row->alpa }}</span></td>
                                <td>{{ $row->percentage === null ? 'Belum ada data' : number_format($row->percentage, 1).'%' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center portal-muted py-5">Tidak ada siswa untuk periode ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
