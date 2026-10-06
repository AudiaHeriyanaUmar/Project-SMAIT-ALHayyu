<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Arsip Jurnal Mengajar</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 18px; }
        .header p { margin: 5px 0 0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table, th, td { border: 1px solid #000; }
        th, td { padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body onload="window.print()">

    <div class="header">
        <h1>REKAPITULASI JURNAL MENGAJAR GURU</h1>
        <h2>SMAIT AL-HAYYU</h2>
        <p>Periode: {{ $namaBulan }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Nama Guru</th>
                <th>Kelas</th>
                <th>Mata Pelajaran</th>
                <th>Materi Pembelajaran</th>
                <th>Catatan pembelajaran</th>
                <th>Rekap absensi</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($jurnals as $index => $jurnal)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $jurnal->tanggal }}</td>
                    <td>{{ $jurnal->user->name ?? '-' }}</td>
                    <td>{{ $jurnal->kelas->nama_kelas ?? '-' }}</td>
                    <td>{{ $jurnal->mataPelajaran->nama_mapel ?? '-' }}</td>
                    <td>{{ $jurnal->materi_pembelajaran }}</td>
                    <td>{{ $jurnal->catatan_kegiatan ?? '-' }}</td>
                    <td>
                        @php($attendanceCounts = $jurnal->absensi->countBy('status'))
                        Hadir: {{ $attendanceCounts['hadir'] ?? 0 }}<br>
                        Izin: {{ $attendanceCounts['izin'] ?? 0 }}<br>
                        Sakit: {{ $attendanceCounts['sakit'] ?? 0 }}<br>
                        Alpa: {{ $attendanceCounts['alpa'] ?? 0 }}
                    </td>
                    <td>{{ $jurnal->status_monitoring === 'verified' ? 'Verified' : 'Pending' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="width: 100%; margin-top: 50px;">
        <div style="float: right; width: 300px; text-align: center;">
            <p>Depok, {{ date('d F Y') }}</p>
            <p>Kepala Sekolah,</p>
            <br><br><br>
            <p><strong>_________________________</strong></p>
        </div>
    </div>

</body>
</html>