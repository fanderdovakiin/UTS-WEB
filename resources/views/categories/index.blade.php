@extends('layouts.app')

@section('judul', 'Kategori')

@section('content')
    <x-page-title title="Kategori Produk"
                  subtitle="Kelompokkan produk agar mudah dicari dan difilter. Nama kategori wajib unik.">
        <x-slot:actions>
            <a href="{{ route('categories.create') }}" class="btn btn--utama">
                <x-icon name="plus" /> Tambah Kategori
            </a>
        </x-slot:actions>
    </x-page-title>

    <div class="tablelist">
        <form method="GET" action="{{ route('categories.index') }}" class="tablelist__toolbar">
            <div class="search-box">
                <x-icon name="cari" />
                <input type="search" name="q" value="{{ request('q') }}" class="input"
                       placeholder="Cari nama kategori…" aria-label="Cari nama kategori">
            </div>
            <div class="tablelist__filters">
                <button type="submit" class="btn btn--netral">Cari</button>
                @if (request('q'))
                    <a href="{{ route('categories.index') }}" class="btn btn--garis">Atur ulang</a>
                @endif
            </div>
        </form>

        <div class="tablelist__body">
            @if ($categories->isEmpty())
                <div class="empty-state">
                    <span class="empty-state__icon"><x-icon name="kategori" :size="20" /></span>
                    <p class="empty-state__title">{{ request('q') ? 'Kategori tidak ditemukan' : 'Belum ada kategori' }}</p>
                    <p class="empty-state__text">
                        {{ request('q') ? 'Tidak ada kategori dengan nama yang cocok. Coba kata kunci lain.'
                                        : 'Tambahkan kategori pertama untuk mengelompokkan produk Anda.' }}
                    </p>
                    @unless (request('q'))
                        <div class="empty-state__aksi">
                            <a href="{{ route('categories.create') }}" class="btn btn--utama">
                                <x-icon name="plus" /> Tambah Kategori
                            </a>
                        </div>
                    @endunless
                </div>
            @else
                <div class="tablelist__scroll">
                    <table class="tablelist__table">
                        <thead>
                            <tr>
                                <th style="width:28%">Nama Kategori</th>
                                <th>Deskripsi</th>
                                <th class="kanan" style="width:110px">Produk</th>
                                <th class="tengah" style="width:170px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($categories as $kategori)
                                <tr>
                                    <td class="tablelist__nomor">{{ $kategori->name }}</td>
                                    <td class="potong" style="color:var(--app-text-muted)">
                                        {{ $kategori->description ?: '—' }}
                                    </td>
                                    <td class="kanan">
                                        <span class="badge badge--biru">{{ $kategori->products_count }}</span>
                                    </td>
                                    <td class="tengah">
                                        <div class="tablelist__aksi">
                                            <a href="{{ route('products.index', ['category_id' => $kategori->id]) }}"
                                               class="btn btn--netral btn--kecil" title="Lihat produk pada kategori ini">
                                                Produk
                                            </a>
                                            <a href="{{ route('categories.edit', $kategori) }}"
                                               class="btn btn--garis btn--kecil" title="Ubah kategori">
                                                <x-icon name="ubah" :size="14" /> Ubah
                                            </a>
                                            <form method="POST" action="{{ route('categories.destroy', $kategori) }}"
                                                  data-konfirmasi="Hapus kategori &quot;{{ $kategori->name }}&quot;? Kategori hanya bisa dihapus bila belum dipakai produk.">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn--tipis btn--kecil" title="Hapus kategori">
                                                    <x-icon name="hapus" :size="14" /> Hapus
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        @if ($categories->hasPages())
            <div class="tablelist__footer">
                {{ $categories->links() }}
            </div>
        @endif
    </div>
@endsection
