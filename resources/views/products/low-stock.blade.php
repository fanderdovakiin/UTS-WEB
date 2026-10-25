@extends('layouts.app')

@section('judul', 'Stok Rendah')

@section('content')
    <x-page-title title="Produk Stok Rendah"
                  subtitle="Daftar produk dengan stok saat ini tidak lebih dari stok minimum — perlu segera dibelikan ulang.">
        <x-slot:actions>
            <a href="{{ route('purchases.create') }}" class="btn btn--utama">
                <x-icon name="plus" /> Catat Pembelian
            </a>
            <a href="{{ route('products.index') }}" class="btn btn--garis">Semua Produk</a>
        </x-slot:actions>
    </x-page-title>

    <div class="tablelist">
        <form method="GET" action="{{ route('products.low-stock') }}" class="tablelist__toolbar">
            <div class="search-box">
                <x-icon name="cari" />
                <input type="search" name="q" value="{{ request('q') }}" class="input"
                       placeholder="Cari nama atau SKU…" aria-label="Cari produk stok rendah">
            </div>
            <div class="tablelist__filters">
                <button type="submit" class="btn btn--netral">Cari</button>
                @if (request('q'))
                    <a href="{{ route('products.low-stock') }}" class="btn btn--garis">Atur ulang</a>
                @endif
            </div>
        </form>

        <div class="tablelist__body">
            @if ($products->isEmpty())
                <div class="empty-state">
                    <span class="empty-state__icon"><x-icon name="centang" :size="20" /></span>
                    <p class="empty-state__title">Tidak ada produk stok rendah</p>
                    <p class="empty-state__text">Semua produk berada di atas batas stok minimum, atau pencarian tidak menemukan hasil.</p>
                </div>
            @else
                <div class="tablelist__scroll">
                    <table class="tablelist__table">
                        <thead>
                            <tr>
                                <th style="width:130px">SKU</th>
                                <th>Nama Produk</th>
                                <th style="width:160px">Kategori</th>
                                <th class="kanan" style="width:110px">Stok</th>
                                <th class="kanan" style="width:130px">Minimum</th>
                                <th class="kanan" style="width:140px">Kurang</th>
                                <th class="tengah" style="width:120px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($products as $produk)
                                <tr>
                                    <td class="tablelist__nomor">{{ $produk->sku }}</td>
                                    <td class="potong">{{ $produk->name }}</td>
                                    <td style="color:var(--app-text-muted)">{{ $produk->category->name }}</td>
                                    <td class="kanan">
                                        <span class="badge badge--merah">{{ $produk->stock }} {{ $produk->unit }}</span>
                                    </td>
                                    <td class="kanan">{{ $produk->minimum_stock }} {{ $produk->unit }}</td>
                                    <td class="kanan">
                                        {{ max(0, $produk->minimum_stock - $produk->stock) }} {{ $produk->unit }}
                                    </td>
                                    <td class="tengah">
                                        <a href="{{ route('products.show', $produk) }}" class="btn btn--garis btn--kecil">
                                            Detail
                                        </a>
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
