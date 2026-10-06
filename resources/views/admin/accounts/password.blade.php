<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#104c37">
    <title>Ubah Password | SMAIT Al-Hayyu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/portal.css') }}" rel="stylesheet">
</head>
<body class="portal-body">
    <nav class="navbar portal-nav py-3">
        <div class="container">
            <a class="navbar-brand" href="{{ route('admin.accounts.index') }}">SMAIT <span class="text-warning">AL-HAYYU</span></a>
            <a href="{{ route('admin.accounts.index') }}" class="btn btn-sm btn-outline-light rounded-pill px-3">Kembali ke akun</a>
        </div>
    </nav>

    <main class="container portal-main">
        <div class="mx-auto" style="max-width:640px">
            <div class="mb-4">
                <p class="portal-eyebrow mb-2">Keamanan akun</p>
                <h1 class="portal-title h2 mb-2">Ubah password</h1>
                <p class="portal-muted mb-0">Atur password baru untuk <strong>{{ $account->name }}</strong> ({{ $account->email }}).</p>
            </div>

            <section class="portal-card p-3 p-md-4 p-lg-5">
                <form action="{{ route('admin.accounts.password.update', $account) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold">Password baru</label>
                        <input id="password" type="password" name="password" class="form-control portal-form-control @error('password') is-invalid @enderror" minlength="8" required autofocus>
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-4">
                        <label for="password_confirmation" class="form-label fw-semibold">Ulangi password baru</label>
                        <input id="password_confirmation" type="password" name="password_confirmation" class="form-control portal-form-control" minlength="8" required>
                    </div>
                    <div class="d-flex flex-column flex-sm-row justify-content-end gap-2">
                        <a href="{{ route('admin.accounts.index') }}" class="btn portal-btn-outline rounded-pill px-4 py-2">Batal</a>
                        <button type="submit" class="btn portal-btn-primary rounded-pill px-4 py-2">Simpan password</button>
                    </div>
                </form>
            </section>
        </div>
    </main>
    <x-account-activity-tracker />
</body>
</html>
