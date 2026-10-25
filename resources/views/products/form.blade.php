@extends('layouts.app')

@section('judul', $product->exists ? 'Ubah Produk' : 'Tambah Produk')

@section('content')
    <x-page-title
        :title="$product->exists ? 'Ubah Produk: ' . $product->name : 'Tambah Produk'"
        :subtitle="$product->exists
            ? 'Harga beli/jual dan data dasar produk. Stok tidak diubah dari halaman ini.'
            : 'Stok awal diisi di sini; stok berikutnya hanya berubah lewat pembelian dan penjualan.'" />

    <form method="POST"
          action="{{ $product->exists ? route('products.update', $product) : route('products.store') }}"
          class="form" data-submit-once>
        @csrf
        @if ($product->exists)
            @method('PUT')
        @endif

        <div class="card">
            <div class="card__header">
                <h2 class="card__title">Identitas Produk</h2>
            </div>
            <div class="card__body">
                <div class="form-grid">
                    <div class="field">
                        <label class="field__label" for="sku">SKU<span class="field__req">*</span></label>
                        <input id="sku" name="sku" type="text" class="input" value="{{ old('sku', $product->sku) }}"
                               placeholder="Contoh: ELE-001" maxlength="40" required
                               aria-invalid="{{ $errors->has('sku') ? 'true' : 'false' }}">
                        @error('sku')
                            <p class="field__error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="field">
                        <label class="field__label" for="name">Nama produk<span class="field__req">*</span></label>
                        <input id="name" name="name" type="text" class="input" value="{{ old('name', $product->name) }}"
                               placeholder="Contoh: Power Bank 10.000mAh" maxlength="150" required
                               aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}">
                        @error('name')
                            <p class="field__error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="field">
                        <label class="field__label" for="category_id">Kategori<span class="field__req">*</span></label>
                        <select id="category_id" name="category_id" class="select" required
                                aria-invalid="{{ $errors->has('category_id') ? 'true' : 'false' }}">
                            <option value="">— pilih kategori —</option>
                            @foreach ($categories as $kategori)
                                <option value="{{ $kategori->id }}"
                                        @selected((string) old('category_id', $product->category_id) === (string) $kategori->id)>
                                    {{ $kategori->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <p class="field__error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="field">
                        <label class="field__label" for="unit">Satuan<span class="field__req">*</span></label>
                        <select id="unit" name="unit" class="select" required>
                            @foreach (['pcs', 'box', 'pack', 'dus', 'kg', 'liter'] as $satuan)
                                <option value="{{ $satuan }}" @selected(old('unit', $product->unit ?: 'pcs') === $satuan)>
                                    {{ $satuan }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card__header">
                <h2 class="card__title">Harga &amp; Stok</h2>
            </div>
            <div class="card__body">
                <div class="form-grid">
                    <div class="field">
                        <label class="field__label" for="purchase_price">Harga beli (Rp)<span class="field__req">*</span></label>
                        <input id="purchase_price" name="purchase_price" type="number" class="input"
                               value="{{ old('purchase_price', $product->purchase_price) }}"
                               min="0" step="0.01" placeholder="120000" required
                               aria-invalid="{{ $errors->has('purchase_price') ? 'true' : 'false' }}">
                        <p class="field__hint">Dipakai sebagai biaya per unit saat pembelian.</p>
                        @error('purchase_price')
                            <p class="field__error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="field">
                        <label class="field__label" for="selling_price">Harga jual (Rp)<span class="field__req">*</span></label>
                        <input id="selling_price" name="selling_price" type="number" class="input"
                               value="{{ old('selling_price', $product->selling_price) }}"
                               min="0" step="0.01" placeholder="185000" required
                               aria-invalid="{{ $errors->has('selling_price') ? 'true' : 'false' }}">
                        <p class="field__hint">Dipakai sebagai harga penjualan di server.</p>
                        @error('selling_price')
                            <p class="field__error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="field">
                        <label class="field__label" for="minimum_stock">Stok minimum<span class="field__req">*</span></label>
                        <input id="minimum_stock" name="minimum_stock" type="number" class="input"
                               value="{{ old('minimum_stock', $product->minimum_stock) }}"
                               min="0" step="1" placeholder="5" required
                               aria-invalid="{{ $errors->has('minimum_stock') ? 'true' : 'false' }}">
                        <p class="field__hint">Batas bawah sebelum produk ditandai stok rendah.</p>
                        @error('minimum_stock')
                            <p class="field__error">{{ $message }}</p>
                        @enderror
                    </div>

                    @unless ($product->exists)
                        <div class="field">
                            <label class="field__label" for="stock">Stok awal<span class="field__req">*</span></label>
                            <input id="stock" name="stock" type="number" class="input"
                                   value="{{ old('stock', 0) }}" min="0" step="1" required
                                   aria-invalid="{{ $errors->has('stock') ? 'true' : 'false' }}">
                            <p class="field__hint">Setelah ini, stok hanya berubah lewat transaksi.</p>
                            @error('stock')
                                <p class="field__error">{{ $message }}</p>
                            @enderror
                        </div>
                    @else
                        <div class="field">
                            <label class="field__label">Stok saat ini</label>
                            <p class="detail-grid__value" style="padding-top:7px">
                                {{ $product->stock }} {{ $product->unit }}
                                @if ($product->isLowStock())
                                    <span class="badge badge--merah">rendah</span>
                                @endif
                            </p>
                            <p class="field__hint">Ubah lewat menu Pembelian/Penjualan atau pembatalannya.</p>
                        </div>
                    @endunless
                </div>
            </div>
        </div>

        <div class="form-aksi">
            <a href="{{ route('products.index') }}" class="btn btn--garis">Batal</a>
            <button type="submit" class="btn btn--utama" data-busy="Menyimpan…">
                {{ $product->exists ? 'Simpan Perubahan' : 'Simpan Produk' }}
            </button>
        </div>
    </form>
@endsection
