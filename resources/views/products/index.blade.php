@extends('layouts.app')

@section('judul', 'Produk')

@section('content')
    <x-page-title title="Daftar Produk"
                  subtitle="Cari berdasarkan nama atau SKU, saring per kategori dan kondisi stok.">
        <x-slot:actions>
            @can('manage-master')
                <a href="{{ route('products.create') }}" class="btn btn--utama">
                    <x-icon name="plus" /> Tambah Produk
                </a>
            @endcan
            <a href="{{ route('products.low-stock') }}" class="btn btn--garis">
                <x-icon name="stok-rendah" /> Stok Rendah
            </a>
        </x-slot:actions>
    </x-page-title>

    <div class="tablelist">
        <form method="GET" action="{{ route('products.index') }}" class="tablelist__toolbar">
            <div class="search-box">
                <x-icon name="cari" />
                <input type="search" name="q" value="{{ request('q') }}" class="input"
                       placeholder="Cari nama atau SKU…" aria-label="Cari nama atau SKU produk">
            </div>

            <div class="tablelist__filters">
                <select name="category_id" class="select" aria-label="Saring kategori">
                    <option value="">Semua kategori</option>
                    @foreach ($categories as $kategori)
                        <option value="{{ $kategori->id }}" @selected((int) request('category_id') === $kategori->id)>
                            {{ $kategori->name }}
                        </option>
                    @endforeach
                </select>

                <select name="stok" class="select" aria-label="Saring kondisi stok">
                    <option value="">Semua stok</option>
                    <option value="rendah" @selected(request('stok') === 'rendah')>Stok rendah</option>
                    <option value="aman" @selected(request('stok') === 'aman')>Stok aman</option>
                </select>

                <button type="submit" class="btn btn--netral">Terapkan</button>
                @if (request()->hasAny(['q', 'category_id', 'stok']))
                    <a href="{{ route('products.index') }}" class="btn btn--garis">Atur ulang</a>
                @endif
            </div>
        </form>

        <div class="tablelist__body">
            @if ($products->isEmpty())
                <div class="empty-state">
                    <span class="empty-state__icon"><x-icon name="produk" :size="20" /></span>
                    <p class="empty-state__title">{{ request()->hasAny(['q', 'category_id', 'stok']) ? 'Produk tidak ditemukan' : 'Belum ada produk' }}</p>
                    <p class="empty-state__text">
                        {{ request()->hasAny(['q', 'category_id', 'stok'])
                            ? 'Tidak ada produk yang cocok dengan pencarian/filter saat ini.'
                            : 'Tambahkan produk pertama untuk mulai mencatat pembelian dan penjualan.' }}
                    </p>
                    @can('manage-master')
                        @unless (request()->hasAny(['q', 'category_id', 'stok']))
                            <div class="empty-state__aksi">
                                <a href="{{ route('products.create') }}" class="btn btn--utama">
                                    <x-icon name="plus" /> Tambah Produk
                                </a>
                            </div>
                        @endunless
                    @endcan
                </div>
            @else
                <div class="tablelist__scroll">
                    <table class="tablelist__table">
                        <thead>
                            <tr>
                                <th style="width:130px">SKU</th>
                                <th>Nama Produk</th>
                                <th style="width:160px">Kategori</th>
                                <th class="kanan" style="width:140px">Harga Jual</th>
                                <th class="kanan" style="width:130px">Stok</th>
                                <th class="tengah" style="width:170px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($products as $produk)
                                <tr>
                                    <td class="tablelist__nomor">{{ $produk->sku }}</td>
                                    <td class="potong">
                                        <a href="{{ route('products.show', $produk) }}" style="text-decoration:none">
                                            {{ $produk->name }}
                                        </a>
                                    </td>
                                    <td style="color:var(--app-text-muted)">{{ $produk->category->name }}</td>
                                    <td class="kanan"><x-rupiah :value="$produk->selling_price" /></td>
                                    <td class="kanan">
                                        {{ $produk->stock }}
                                        <span style="color:var(--app-text-muted); font-size:11px">{{ $produk->unit }}</span>
                                        @if ($produk->isLowStock())
                                            <span class="badge badge--merah" title="Di bawah stok minimum {{ $produk->minimum_stock }}">rendah</span>
                                        @endif
                                    </td>
                                    <td class="tengah">
                                        <div class="tablelist__aksi">
                                            <a href="{{ route('products.show', $produk) }}" class="btn btn--garis btn--kecil" title="Lihat detail">
                                                <x-icon name="lihat" :size="14" /> Lihat
                                            </a>
                                            @can('manage-master')
                                                <a href="{{ route('products.edit', $produk) }}" class="btn btn--netral btn--kecil" title="Ubah produk">
                                                    <x-icon name="ubah" :size="14" />
                                                </a>
                                                <form method="POST" action="{{ route('products.destroy', $produk) }}"
                                                      data-konfirmasi="Hapus produk &quot;{{ $produk->name }}&quot;? Produk yang sudah tercatat dalam transaksi tidak dapat dihapus.">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn--tipis btn--kecil" title="Hapus produk">
                                                        <x-icon name="hapus" :size="14" />
                                                    </button>
                                                </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        @if ($products->hasPages())
            <div class="tablelist__footer">
                {{ $products->links() }}
            </div>
        @endif
    </div>
@endsection
