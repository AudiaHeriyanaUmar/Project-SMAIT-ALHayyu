<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#104c37">
    <title>{{ $editing ? 'Edit akun' : 'Tambah akun' }} | SMAIT Al-Hayyu</title>
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
        <div class="mx-auto" style="max-width:720px">
            <div class="mb-4">
                <p class="portal-eyebrow mb-2">Administrasi pengguna</p>
                <h1 class="portal-title h2 mb-2">{{ $editing ? 'Ubah informasi akun' : 'Tambah akun baru' }}</h1>
                <p class="portal-muted mb-0">Pilih role sesuai akses yang dibutuhkan.</p>
            </div>

            <section class="portal-card p-3 p-md-4 p-lg-5">
                <form action="{{ $editing ? route('admin.accounts.update', $account) : route('admin.accounts.store') }}" method="POST">
                    @csrf
                    @if($editing) @method('PUT') @endif

                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold">Nama</label>
                        <input id="name" name="name" value="{{ old('name', $account->name) }}"
                               class="form-control portal-form-control @error('name') is-invalid @enderror" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold">Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email', $account->email) }}"
                               class="form-control portal-form-control @error('email') is-invalid @enderror" required>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="role" class="form-label fw-semibold">Role akun</label>
                        <select id="role" name="role" class="form-select portal-form-control @error('role') is-invalid @enderror" required>
                            @foreach($roles as $role)
                                <option value="{{ $role }}"
                                        @selected(old('role', $account->role ?? 'guru') === $role)
                                        @disabled($editing && auth()->user()->is($account) && $role !== 'admin')>
                                    {{ str_replace('_', ' ', ucfirst($role)) }}
                                </option>
                            @endforeach
                        </select>
                        @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    @if(!$editing)
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="password" class="form-label fw-semibold">Password awal</label>
                                <input id="password" type="password" name="password" class="form-control portal-form-control" minlength="8" required>
                            </div>
                            <div class="col-md-6">
                                <label for="password_confirmation" class="form-label fw-semibold">Ulangi password</label>
                                <input id="password_confirmation" type="password" name="password_confirmation" class="form-control portal-form-control" minlength="8" required>
                            </div>
                        </div>
                        @error('password')<div class="alert alert-danger rounded-4">{{ $message }}</div>@enderror
                    @endif

                    @if($editing && auth()->user()->is($account))
                        <div class="alert alert-info rounded-4">Role akun admin Anda sendiri tidak dapat diubah.</div>
                    @endif

                    <div class="d-flex flex-column flex-sm-row justify-content-end gap-2">
                        <a href="{{ route('admin.accounts.index') }}" class="btn portal-btn-outline rounded-pill px-4 py-2">Batal</a>
                        <button type="submit" class="btn portal-btn-primary rounded-pill px-4 py-2">
                            {{ $editing ? 'Simpan perubahan' : 'Buat akun' }}
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </main>
    <x-account-activity-tracker />
</body>
</html>
