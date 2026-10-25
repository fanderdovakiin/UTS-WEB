@extends('layouts.app')

@section('judul', $category->exists ? 'Ubah Kategori' : 'Tambah Kategori')

@section('content')
    <x-page-title
        :title="$category->exists ? 'Ubah Kategori: ' . $category->name : 'Tambah Kategori'"
        :subtitle="$category->exists ? 'Perbarui nama atau deskripsi kategori.'
                                     : 'Kategori baru akan langsung tersedia pada form produk.'" />

    <form method="POST"
          action="{{ $category->exists ? route('categories.update', $category) : route('categories.store') }}"
          class="form" data-submit-once>
        @csrf
        @if ($category->exists)
            @method('PUT')
        @endif

        <div class="card">
            <div class="card__header">
                <h2 class="card__title">Data Kategori</h2>
            </div>
            <div class="card__body">
                <div class="form-grid">
                    <div class="field">
                        <label class="field__label" for="name">Nama kategori<span class="field__req">*</span></label>
                        <input id="name" name="name" type="text" class="input"
                               value="{{ old('name', $category->name) }}"
                               placeholder="Contoh: Elektronik" maxlength="100" required
                               aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}">
                        @error('name')
                            <p class="field__error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="field">
                        <label class="field__label" for="description">Deskripsi</label>
                        <input id="description" name="description" type="text" class="input"
                               value="{{ old('description', $category->description) }}"
                               placeholder="Contoh: Perangkat dan aksesori listrik" maxlength="255">
                        <p class="field__hint">Opsional — membantu membedakan kategori yang mirip.</p>
                        @error('description')
                            <p class="field__error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="form-aksi">
            <a href="{{ route('categories.index') }}" class="btn btn--garis">Batal</a>
            <button type="submit" class="btn btn--utama" data-busy="Menyimpan…">
                {{ $category->exists ? 'Simpan Perubahan' : 'Simpan Kategori' }}
            </button>
        </div>
    </form>
@endsection
