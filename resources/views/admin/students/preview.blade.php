<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pratinjau impor siswa | SMAIT Al-Hayyu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/portal.css') }}" rel="stylesheet">
</head>
<body class="portal-body">
    <main class="container portal-main">
        <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
            <div>
                <p class="portal-eyebrow mb-2">Pemeriksaan data</p>
                <h1 class="portal-title h2 mb-2">Pratinjau impor siswa</h1>
                <p class="portal-muted mb-0">{{ count($rows) }} siswa valid. Tidak ada data yang disimpan sampai Anda mengonfirmasi.</p>
            </div>
            <a href="{{ route('admin.students.index') }}" class="btn portal-btn-outline rounded-pill px-4">Batalkan</a>
        </header>

        <section class="portal-card mb-4">
            <div class="table-responsive">
                <table class="table portal-table table-hover mb-0">
                    <thead><tr><th>NISN</th><th>Nama lengkap</th><th>Kelas</th><th>Baris CSV</th></tr></thead>
                    <tbody>
                        @foreach($rows as $row)
                            <tr>
                                <td>{{ $row['nisn'] }}</td>
                                <td>{{ $row['nama_lengkap'] }}</td>
                                <td>{{ $row['nama_kelas'] }}</td>
                                <td>{{ $row['line'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
        <form action="{{ route('admin.students.import.confirm') }}" method="POST" class="d-flex justify-content-end">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <button type="submit" class="btn portal-btn-primary rounded-pill px-4">Konfirmasi impor {{ count($rows) }} siswa</button>
        </form>
    </main>
</body>
</html>
