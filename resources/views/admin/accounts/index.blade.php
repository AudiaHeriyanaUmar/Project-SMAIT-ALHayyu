<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#104c37">
    <title>Kelola Akun | SMAIT Al-Hayyu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/portal.css') }}" rel="stylesheet">
</head>
<body class="portal-body">
    <nav class="navbar navbar-expand-lg portal-nav py-3">
        <div class="container">
            <a class="navbar-brand" href="{{ route('admin.dashboard') }}">SMAIT <span class="text-warning">AL-HAYYU</span></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#accountNavigation"
                    aria-controls="accountNavigation" aria-expanded="false" aria-label="Buka navigasi">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="accountNavigation">
                <div class="navbar-nav ms-auto align-items-lg-center gap-lg-3">
                    <a class="nav-link" href="{{ route('admin.dashboard') }}">Monitoring jurnal</a>
                    <a class="nav-link" href="{{ route('admin.absensi.index') }}">Rekap absensi</a>
                    <a class="nav-link" href="{{ route('admin.students.index') }}">Kelola siswa</a>
                    <a class="nav-link" href="{{ route('admin.classes.index') }}">Kelola kelas</a>
                    <a class="nav-link active" href="{{ route('admin.accounts.index') }}">Kelola akun</a>
                    <a class="nav-link" href="{{ route('admin.audit.index') }}">Audit</a>
                    <a class="nav-link" href="{{ route('admin.backup.index') }}">Backup</a>
                    <span class="nav-link d-none d-lg-inline">{{ auth()->user()->name }}</span>
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
                <p class="portal-eyebrow mb-2">Administrasi pengguna</p>
                <h1 class="portal-title h2 mb-2">Kelola akun</h1>
                <p class="portal-muted mb-0">Tambah akun, atur akses, kelola password, dan pantau aktivitas.</p>
            </div>
            <a href="{{ route('admin.accounts.create') }}" class="btn portal-btn-primary rounded-pill px-4 py-2">+ Tambah akun</a>
        </header>

        @if(session('success'))
            <div class="alert alert-success border-0 rounded-4 shadow-sm" role="alert">{{ session('success') }}</div>
        @endif
        @if($errors->has('account'))
            <div class="alert alert-danger border-0 rounded-4 shadow-sm" role="alert">{{ $errors->first('account') }}</div>
        @endif

        <section class="row g-3 mb-4" aria-label="Ringkasan akun">
            <div class="col-6 col-xl-3">
                <article class="portal-stat p-3 p-lg-4">
                    <p class="portal-stat-label mb-3">Total akun</p>
                    <p class="portal-stat-value mb-0">{{ number_format($accounts->total()) }}</p>
                </article>
            </div>
            <div class="col-6 col-xl-3">
                <article class="portal-stat p-3 p-lg-4">
                    <p class="portal-stat-label mb-3">Aktif sekarang</p>
                    <p class="portal-stat-value mb-0" style="color:#176b4b">{{ number_format($onlineCount) }}</p>
                </article>
            </div>
            <div class="col-6 col-xl-3">
                <article class="portal-stat p-3 p-lg-4">
                    <p class="portal-stat-label mb-3">Guru</p>
                    <p class="portal-stat-value mb-0">{{ number_format($roleCounts['guru'] ?? 0) }}</p>
                </article>
            </div>
            <div class="col-6 col-xl-3">
                <article class="portal-stat p-3 p-lg-4">
                    <p class="portal-stat-label mb-3">Admin</p>
                    <p class="portal-stat-value mb-0">{{ number_format($roleCounts['admin'] ?? 0) }}</p>
                </article>
            </div>
        </section>

        <section class="portal-card">
            <div class="portal-card-header p-3 p-lg-4">
                <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3">
                    <div>
                        <h2 class="h5 fw-bold mb-1">Daftar akun</h2>
                        <p class="portal-muted small mb-0">Jam aktif harian diringkas terus; detail sesi dan audit tindakan disimpan selama 14 hari.</p>
                    </div>
                    <form action="{{ route('admin.accounts.index') }}" method="GET" class="row g-2">
                        <div class="col-8 col-sm-auto">
                            <label for="q" class="visually-hidden">Cari akun</label>
                            <input id="q" type="search" name="q" value="{{ $filters['q'] ?? '' }}"
                                   class="form-control portal-form-control" placeholder="Nama atau email">
                        </div>
                        <div class="col-4 col-sm-auto">
                            <label for="role" class="visually-hidden">Filter role</label>
                            <select id="role" name="role" class="form-select portal-form-control">
                                <option value="">Semua role</option>
                                <option value="admin" @selected(($filters['role'] ?? '') === 'admin')>Admin</option>
                                <option value="guru" @selected(($filters['role'] ?? '') === 'guru')>Guru</option>
                                <option value="calon_siswa" @selected(($filters['role'] ?? '') === 'calon_siswa')>Calon siswa</option>
                            </select>
                        </div>
                        <div class="col-12 col-sm-auto d-flex gap-2">
                            <button type="submit" class="btn portal-btn-primary rounded-pill px-3 flex-grow-1">Cari</button>
                            <a href="{{ route('admin.accounts.index') }}" class="btn portal-btn-outline rounded-pill px-3">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table portal-table table-hover mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Akun</th>
                            <th scope="col">Role</th>
                            <th scope="col">Status</th>
                            <th scope="col">Aktif hari ini</th>
                            <th scope="col">Aktif 7 hari</th>
                            <th scope="col">Aktivitas terakhir</th>
                            <th scope="col">Kelola</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($accounts as $account)
                            @php
                                $todaySeconds = $activity[$account->id]->today_seconds ?? 0;
                                $weekSeconds = $activity[$account->id]->week_seconds ?? 0;
                                $lastActivity = $sessions[$account->id]->last_activity_at ?? null;
                                $isOnline = ($sessions[$account->id]->is_online ?? 0) == 1;
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-bold">{{ $account->name }}</div>
                                    <div class="portal-muted small">{{ $account->email }}</div>
                                </td>
                                <td><span class="badge rounded-pill text-bg-light">{{ str_replace('_', ' ', ucfirst($account->role)) }}</span></td>
                                <td>
                                    @if($isOnline)
                                        <span class="badge rounded-pill text-bg-success">Online</span>
                                    @else
                                        <span class="badge rounded-pill text-bg-secondary">Offline</span>
                                    @endif
                                </td>
                                <td>{{ intdiv((int) $todaySeconds, 3600) }}j {{ str_pad((string) intdiv((int) $todaySeconds % 3600, 60), 2, '0', STR_PAD_LEFT) }}m</td>
                                <td>{{ intdiv((int) $weekSeconds, 3600) }}j {{ str_pad((string) intdiv((int) $weekSeconds % 3600, 60), 2, '0', STR_PAD_LEFT) }}m</td>
                                <td class="text-nowrap">
                                    @if($lastActivity)
                                        {{ \Illuminate\Support\Carbon::parse($lastActivity)->diffForHumans() }}
                                    @else
                                        <span class="portal-muted">Belum ada aktivitas</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-2">
                                        <a href="{{ route('admin.accounts.edit', $account) }}" class="btn btn-sm portal-btn-outline rounded-pill">Edit</a>
                                        <a href="{{ route('admin.accounts.password.edit', $account) }}" class="btn btn-sm portal-btn-outline rounded-pill">Password</a>
                                        @if(!auth()->user()->is($account))
                                            <form action="{{ route('admin.accounts.destroy', $account) }}" method="POST"
                                                  onsubmit="return confirm('Hapus akses akun {{ addslashes($account->name) }}? Riwayat jurnal tetap disimpan.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill">Hapus</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center portal-muted py-5">Tidak ada akun yang cocok dengan pencarian.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($accounts->hasPages())
                <div class="border-top p-3 p-lg-4">{{ $accounts->links('pagination::bootstrap-5') }}</div>
            @endif
        </section>

        <p class="portal-muted small mt-3 mb-0">Akun yang dihapus dinonaktifkan dan diarsipkan; jurnal serta riwayat aktivitasnya tetap dipertahankan.</p>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <x-account-activity-tracker />
</body>
</html>
