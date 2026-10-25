@extends('layouts.app')

@section('judul', $user->exists ? 'Ubah Pengguna' : 'Tambah Pengguna')

@section('content')
    <x-page-title
        :title="$user->exists ? 'Ubah Akun: ' . $user->name : 'Tambah Akun Pengguna'"
        subtitle="Role admin dapat mengelola master data, akun, dan membatalkan transaksi. Role staff hanya membuat transaksi." />

    <form method="POST"
          action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}"
          class="form" data-submit-once>
        @csrf
        @if ($user->exists)
            @method('PUT')
        @endif

        <div class="card">
            <div class="card__header">
                <h2 class="card__title">Data Akun</h2>
            </div>
            <div class="card__body">
                <div class="form-grid">
                    <div class="field">
                        <label class="field__label" for="name">Nama<span class="field__req">*</span></label>
                        <input id="name" name="name" type="text" class="input"
                               value="{{ old('name', $user->name) }}" maxlength="100" required
                               aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}">
                        @error('name')
                            <p class="field__error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="field">
                        <label class="field__label" for="email">Email<span class="field__req">*</span></label>
                        <input id="email" name="email" type="email" class="input"
                               value="{{ old('email', $user->email) }}" maxlength="100" required
                               aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}">
                        @error('email')
                            <p class="field__error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="field">
                        <label class="field__label" for="role">Role<span class="field__req">*</span></label>
                        <select id="role" name="role" class="select" required>
                            <option value="staff" @selected(old('role', $user->role ?: 'staff') === 'staff')>Staff</option>
                            <option value="admin" @selected(old('role', $user->role) === 'admin')>Admin / Owner</option>
                        </select>
                        <p class="field__hint">Akses dicek di server, bukan hanya di tampilan.</p>
                        @error('role')
                            <p class="field__error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="field">
                        <label class="field__label" for="password">
                            Password
                            @unless ($user->exists)
                                <span class="field__req">*</span>
                            @endunless
                        </label>
                        <input id="password" name="password" type="password" class="input"
                               autocomplete="new-password"
                               placeholder="{{ $user->exists ? 'Kosongkan bila tidak diganti' : 'Minimal 8 karakter' }}"
                               @unless ($user->exists) required @endunless
                               aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}">
                        @error('password')
                            <p class="field__error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="field">
                        <label class="field__label" for="password_confirmation">
                            Konfirmasi password
                            @unless ($user->exists)
                                <span class="field__req">*</span>
                            @endunless
                        </label>
                        <input id="password_confirmation" name="password_confirmation" type="password"
                               class="input" autocomplete="new-password"
                               placeholder="Ulangi password"
                               @unless ($user->exists) required @endunless>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-aksi">
            <a href="{{ route('users.index') }}" class="btn btn--garis">Batal</a>
            <button type="submit" class="btn btn--utama" data-busy="Menyimpan…">
                {{ $user->exists ? 'Simpan Perubahan' : 'Simpan Akun' }}
            </button>
        </div>
    </form>
@endsection
