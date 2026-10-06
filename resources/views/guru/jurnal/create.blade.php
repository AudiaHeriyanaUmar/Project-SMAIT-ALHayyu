<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Jurnal Guru Real-time</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow border-0">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Form Input Jurnal Mengajar & Pembelajaran</h4>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('guru.jurnal.store') }}" method="POST">
                            @csrf
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Kelas</label>
                                        <select name="kelas_id" class="form-select" required>
                                <option value="">-- Pilih Kelas --</option>
                                    @foreach($kelasList as $kelas)
                                    <option value="{{ $kelas->id }}">{{ $kelas->nama_kelas }}</option>
                                    @endforeach
                                </select>
                            </div>
                        <div class="col-md-6">
                            <label class="form-label">Mata Pelajaran</label>
                                <select name="mata_pelajaran_id" class="form-select" required>
                                    <option value="">-- Pilih Mata Pelajaran --</option>
                                    @foreach($mapelList as $mapel)
                                    <option value="{{ $mapel->id }}">{{ $mapel->nama_mapel }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Kelas</label>
                                    <input type="text" name="kelas" class="form-control" placeholder="Contoh: X MIPA 1" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Mata Pelajaran</label>
                                    <input type="text" name="mata_pelajaran" class="form-control" placeholder="Contoh: Matematika" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Materi Pembelajaran</label>
                                <textarea name="materi_pembelajaran" class="form-control" rows="3" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Catatan Tambahan / Absensi Siswa</label>
                                <textarea name="catatan_kegiatan" class="form-control" rows="2" placeholder="Catatan siswa tidak hadir / kendala kelas..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Simpan Jurnal Real-time</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
