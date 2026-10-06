<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Audit admin | SMAIT Al-Hayyu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/portal.css') }}" rel="stylesheet">
</head>
<body class="portal-body">
    <nav class="navbar navbar-expand-lg portal-nav py-3">
        <div class="container">
            <a class="navbar-brand" href="{{ route('admin.dashboard') }}">SMAIT <span class="text-warning">AL-HAYYU</span></a>
            <div class="navbar-nav ms-auto"><a class="nav-link" href="{{ route('admin.dashboard') }}">Kembali ke dashboard</a></div>
        </div>
    </nav>
    <main class="container portal-main">
        <header class="mb-4">
            <p class="portal-eyebrow mb-2">Keamanan dan akuntabilitas</p>
            <h1 class="portal-title h2 mb-2">Audit tindakan admin</h1>
            <p class="portal-muted mb-0">Log admin dan detail sesi aktivitas disimpan selama 14 hari. Ringkasan jam aktif harian tidak dihapus oleh retensi ini.</p>
        </header>
        <section class="portal-card mb-4 p-3">
            <form method="GET" class="row g-2">
                <div class="col-md-4">
                    <label for="action" class="visually-hidden">Jenis tindakan</label>
                    <select id="action" name="action" class="form-select portal-form-control">
                        <option value="">Semua tindakan</option>
                        @foreach($actions as $action)<option value="{{ $action }}" @selected(($filters['action'] ?? '') === $action)>{{ $action }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="q" class="visually-hidden">Cari audit</label>
                    <input id="q" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control portal-form-control" placeholder="Cari deskripsi atau ID data">
                </div>
                <div class="col-md-2"><button class="btn portal-btn-primary rounded-pill w-100">Filter</button></div>
            </form>
        </section>
        <section class="portal-card">
            <div class="table-responsive">
                <table class="table portal-table table-hover mb-0">
                    <thead><tr><th>Waktu</th><th>Admin</th><th>Tindakan</th><th>Deskripsi / perubahan</th></tr></thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td class="text-nowrap">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                                <td>{{ $log->admin->name ?? 'Akun dihapus' }}</td>
                                <td><span class="badge text-bg-light">{{ $log->action }}</span></td>
                                <td>
                                    {{ $log->description }}
                                    @if($log->metadata)
                                        <details class="small mt-1">
                                            <summary>Lihat rincian</summary>
                                            <pre class="small text-wrap mt-2 mb-0">{{ json_encode($log->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                        </details>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center portal-muted py-5">Belum ada tindakan dalam periode retensi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($logs->hasPages())<div class="border-top p-3">{{ $logs->links('pagination::bootstrap-5') }}</div>@endif
        </section>
    </main>
</body>
</html>
