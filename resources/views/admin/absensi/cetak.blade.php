<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Absensi Siswa - SMAIT Al-Hayyu</title>
    <style>
        body { color: #17231c; font: 12px Arial, sans-serif; }
        h1, p { margin: 0 0 8px; }
        header { border-bottom: 2px solid #176b4b; margin-bottom: 18px; padding-bottom: 12px; text-align: center; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #aebbb2; padding: 7px; text-align: left; vertical-align: top; }
        th { background: #e8f3ed; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body onload="window.print()">
    <button class="no-print" type="button" onclick="window.print()">Cetak laporan</button>
    <header>
        <h1>LAPORAN ABSENSI SISWA</h1>
        <p>SMAIT AL-HAYYU</p>
        <p>
            Periode:
            {{ $filters['tanggal_dari'] ?? 'Awal' }} – {{ $filters['tanggal_sampai'] ?? 'Sekarang' }}
            @if(!empty($filters['kelas_id'])) · Kelas {{ $attendance->first()?->jurnal->kelas->nama_kelas ?? $filters['kelas_id'] }} @endif
        </p>
    </header>
    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>Tanggal / Jam</th>
                <th>Siswa</th>
                <th>Kelas</th>
                <th>Mata pelajaran</th>
                <th>Guru</th>
                <th>Status</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($attendance as $index => $record)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ \Illuminate\Support\Carbon::parse($record->jurnal->tanggal)->format('d/m/Y') }} · {{ $record->jurnal->jam_ke }}</td>
                    <td>{{ $record->siswa->nama_lengkap }} ({{ $record->siswa->nisn }})</td>
                    <td>{{ $record->jurnal->kelas->nama_kelas ?? '-' }}</td>
                    <td>{{ $record->jurnal->mataPelajaran->nama_mapel ?? '-' }}</td>
                    <td>{{ $record->jurnal->user->name ?? '-' }}</td>
                    <td>{{ ucfirst($record->status) }}</td>
                    <td>{{ $record->keterangan ?: '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="8">Tidak ada catatan absensi pada filter ini.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
