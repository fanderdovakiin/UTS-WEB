<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Masuk · RuangStok</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>
    <div class="login-page">
        <div class="login-shell">
            <div class="login-form-panel">
                <a href="{{ route('login') }}" class="login-brand">
                    <span class="sidebar-brand__mark">R</span>
                    <span class="sidebar-brand__text">RuangStok</span>
                </a>

                <h1 class="login-title">Masuk ke akun Anda</h1>
                <p class="login-subtitle">
                    Sistem inventori &amp; penjualan untuk usaha distribusi kecil.
                    Silakan masuk dengan email dan password Anda.
                </p>

                @if (session('success'))
                    <div class="alert alert--sukses" role="status" style="margin-bottom:14px">
                        <x-icon name="centang" />
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                <form method="POST" action="{{ route('login.attempt') }}" class="login-form" data-submit-once data-busy="Memeriksa…">
                    @csrf

                    <div class="field">
                        <label class="field__label" for="email">Email<span class="field__req">*</span></label>
                        <input id="email" name="email" type="email" class="input" style="min-height:44px"
                               placeholder="nama@contoh.com" value="{{ old('email') }}"
                               autocomplete="username" required
                               aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}">
                        @error('email')
                            <p class="field__error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="field">
                        <label class="field__label" for="password">Password<span class="field__req">*</span></label>
                        <input id="password" name="password" type="password" class="input" style="min-height:44px"
                               placeholder="••••••••" autocomplete="current-password" required
                               aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}">
                        @error('password')
                            <p class="field__error">{{ $message }}</p>
                        @enderror
                    </div>

                    <label style="display:flex; align-items:center; gap:8px; font-size:12.5px; color:var(--app-text-muted)">
                        <input type="checkbox" name="remember" value="1" style="accent-color:var(--color-primary)">
                        Ingat saya di perangkat ini
                    </label>

                    <button type="submit" class="btn btn--utama login-submit">Masuk</button>
                </form>

                <div class="login-demo">
                    <strong>Akun demo (hanya untuk lokal/praktikum):</strong><br>
                    Admin — <code>owner@ruangstok.test</code> / <code>ruangstok123</code><br>
                    Staff — <code>staff@ruangstok.test</code> / <code>ruangstok123</code>
                    <br><span style="color:var(--app-danger-text)">Jangan gunakan akun ini di production.</span>
                </div>
            </div>

            <div class="login-art-panel" aria-hidden="true">
                <h2 class="login-art-title">Satu tempat untuk stok dan penjualan</h2>
                <p class="login-art-text">
                    RuangStok mencatat setiap pembelian dan penjualan beserta pengaruhnya
                    terhadap stok secara atomik, sehingga angka di dashboard selalu berasal
                    dari database.
                </p>
                <ul class="login-art-list">
                    <li><x-icon name="centang" :size="15" /> Pembelian &amp; penjualan multi-item</li>
                    <li><x-icon name="centang" :size="15" /> Penolakan oversell dan pembatalan aman</li>
                    <li><x-icon name="centang" :size="15" /> Peran Admin &amp; Staff yang ditegakkan di server</li>
                </ul>
            </div>
        </div>
    </div>
</body>

</html>
