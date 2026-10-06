<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jurnal Guru Online - SMAIT Al-Hayyu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container my-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Jurnal Mengajar Online Guru</h2>
            <a href="{{ route('guru.jurnal.create') }}" class="btn btn-primary">+ Isi Jurnal Hari Ini</a>
        </div>
        
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <table class="table table-striped mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Tanggal</th>
                            <th>Jam Ke</th>
                            <th>Kelas</th>
                            <th>Mata Pelajaran</th>
                            <th>Materi</th>
                            <th>Status Monitoring</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($jurnals as $jurnal)
                        <tr>
                        <td>{{ $jurnal->tanggal }}</td>
                        <td>{{ $jurnal->jam_ke }}</td>
                        <!-- Panggil nama_kelas dan nama_mapel dari relasi -->
                        <td>{{ $jurnal->kelas->nama_kelas ?? 'Data terhapus' }}</td>
                        <td>{{ $jurnal->mataPelajaran->nama_mapel ?? 'Data terhapus' }}</td>
                        <td>{{ $jurnal->materi_pembelajaran }}</td>
                        <td><span class="badge bg-warning text-dark">{{ ucfirst($jurnal->status_monitoring) }}</span></td>
                        </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-3">Belum ada jurnal yang diisi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
