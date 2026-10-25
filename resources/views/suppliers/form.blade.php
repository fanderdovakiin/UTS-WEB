@extends('layouts.app')

@section('judul', $supplier->exists ? 'Ubah Pemasok' : 'Tambah Pemasok')

@section('content')
    <x-page-title
        :title="$supplier->exists ? 'Ubah Pemasok: ' . $supplier->name : 'Tambah Pemasok'"
        subtitle="Data kontak dipakai pada transaksi pembelian." />

    <form method="POST"
          action="{{ $supplier->exists ? route('suppliers.update', $supplier) : route('suppliers.store') }}"
          class="form" data-submit-once>
        @csrf
        @if ($supplier->exists)
            @method('PUT')
        @endif

        <div class="card">
            <div class="card__header">
                <h2 class="card__title">Data Pemasok</h2>
            </div>
            <div class="card__body">
                <div class="form-grid">
                    <div class="field">
                        <label class="field__label" for="name">Nama pemasok<span class="field__req">*</span></label>
                        <input id="name" name="name" type="text" class="input"
                               value="{{ old('name', $supplier->name) }}" maxlength="100" required
                               placeholder="Contoh: PT Sumber Elektronik Jaya"
                               aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}">
                        @error('name')
                            <p class="field__error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="field">
                        <label class="field__label" for="phone">Telepon</label>
                        <input id="phone" name="phone" type="text" class="input"
                               value="{{ old('phone', $supplier->phone) }}" maxlength="30"
                               placeholder="0215550198">
                        @error('phone')
                            <p class="field__error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="field">
                        <label class="field__label" for="email">Email</label>
                        <input id="email" name="email" type="email" class="input"
                               value="{{ old('email', $supplier->email) }}" maxlength="100"
                               placeholder="sales@pemasok.test"
                               aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}">
                        @error('email')
                            <p class="field__error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="field">
                        <label class="field__label" for="address">Alamat</label>
                        <input id="address" name="address" type="text" class="input"
                               value="{{ old('address', $supplier->address) }}" maxlength="255"
                               placeholder="Jalan, kota">
                        @error('address')
                            <p class="field__error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="form-aksi">
            <a href="{{ route('suppliers.index') }}" class="btn btn--garis">Batal</a>
            <button type="submit" class="btn btn--utama" data-busy="Menyimpan…">
                {{ $supplier->exists ? 'Simpan Perubahan' : 'Simpan Pemasok' }}
            </button>
        </div>
    </form>
@endsection
