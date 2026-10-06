<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPMB Digital - SMAIT Al-Hayyu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow border-0">
                    <div class="card-header bg-success text-white py-3">
                        <h4 class="mb-0 text-center">Formulir Pendaftaran SPMB Online SMAIT Al-Hayyu</h4>
                    </div>
                    <div class="card-body p-4">
                        @if(session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif

                        <form action="{{ route('spmb.store') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Nama Lengkap</label>
                                <input type="text" name="nama_lengkap" class="form-control" required placeholder="Sesuai Ijazah">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email Aktif</label>
                                <input type="email" name="email" class="form-control" required placeholder="contoh@gmail.com">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nomor WhatsApp</label>
                                <input type="text" name="no_whatsapp" class="form-control" required placeholder="08xxxxxxxxxx">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Asal Sekolah (SMP/MTs)</label>
                                <input type="text" name="asal_sekolah" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Pilihan Jurusan</label>
                                <select name="jurusan_pilihan" class="form-select" required>
                                    <option value="IPA">MIPA (Matematika & IPA)</option>
                                    <option value="IPS">IPS (Ilmu Pengetahuan Sosial)</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-success w-100 py-2">Kirim Pendaftaran & Dapatkan Konfirmasi WA</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
