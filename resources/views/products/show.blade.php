@extends('layouts.app')

@section('judul', 'Detail Produk')

@section('content')
    <x-page-title :title="$product->name"
                  :subtitle="$product->sku . ' · ' . $product->category->name">
        <x-slot:actions>
            <a href="{{ route('products.index') }}" class="btn btn--garis">Kembali</a>
            @can('manage-master')
                <a href="{{ route('products.edit', $product) }}" class="btn btn--utama">
                    <x-icon name="ubah" /> Ubah Produk
                </a>
            @endcan
            <a href="{{ route('sales.create', ['produk' => $product->id]) }}" class="btn btn--netral">
                <x-icon name="penjualan" /> Catat Penjualan
            </a>
        </x-slot:actions>
    </x-page-title>

    <div class="card">
        <div class="card__header">
            <h2 class="card__title">Informasi Produk</h2>
            @if ($product->isLowStock())
                <span class="badge badge--merah">Stok rendah — tersisa {{ $product->stock }} {{ $product->unit }}</span>
            @else
                <span class="badge badge--hijau">Stok aman</span>
            @endif
        </div>
        <div class="card__body">
            <div class="detail-grid">
                <div>
                    <span class="detail-grid__label">SKU</span>
                    <p class="detail-grid__value">{{ $product->sku }}</p>
                </div>
                <div>
                    <span class="detail-grid__label">Kategori</span>
                    <p class="detail-grid__value">{{ $product->category->name }}</p>
                </div>
                <div>
                    <span class="detail-grid__label">Harga beli</span>
                    <p class="detail-grid__value"><x-rupiah :value="$product->purchase_price" /></p>
                </div>
                <div>
                    <span class="detail-grid__label">Harga jual</span>
                    <p class="detail-grid__value"><x-rupiah :value="$product->selling_price" /></p>
                </div>
                <div>
                    <span class="detail-grid__label">Stok saat ini</span>
                    <p class="detail-grid__value">{{ $product->stock }} {{ $product->unit }}</p>
                </div>
                <div>
                    <span class="detail-grid__label">Stok minimum</span>
                    <p class="detail-grid__value">{{ $product->minimum_stock }} {{ $product->unit }}</p>
                </div>
                <div>
                    <span class="detail-grid__label">Satuan</span>
                    <p class="detail-grid__value">{{ $product->unit }}</p>
                </div>
                <div>
                    <span class="detail-grid__label">Diperbarui</span>
                    <p class="detail-grid__value">{{ $product->updated_at->format('d/m/Y H:i') }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="dashboard-dua-kolom">
        <section class="card" aria-label="Riwayat pembelian">
            <div class="card__header">
                <h2 class="card__title">Riwayat Pembelian (Stock-in)</h2>
            </div>
            @if ($pembelian->isEmpty())
                <div class="empty-state">
                    <p class="empty-state__title">Belum pernah dibelikan</p>
                    <p class="empty-state__text">Produk ini belum tercatat pada transaksi pembelian mana pun.</p>
                </div>
            @else
                <div class="tablelist__body">
                    <div class="tablelist__scroll">
                        <table class="tablelist__table">
                            <thead>
                                <tr>
                                    <th>Nomor</th>
                                    <th>Tanggal</th>
                                    <th class="kanan">Qty</th>
                                    <th class="kanan">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($pembelian as $baris)
                                    <tr>
                                        <td>
                                            <a class="tablelist__nomor" href="{{ route('purchases.show', $baris->purchase) }}">
                                                {{ $baris->purchase->purchase_number }}
                                            </a>
                                        </td>
                                        <td>{{ $baris->purchase->purchased_at->format('d/m/Y') }}</td>
                                        <td class="kanan">{{ $baris->quantity }}</td>
                                        <td class="kanan"><x-rupiah :value="$baris->subtotal" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </section>

        <section class="card" aria-label="Riwayat penjualan">
            <div class="card__header">
                <h2 class="card__title">Riwayat Penjualan (Stock-out)</h2>
            </div>
            @if ($penjualan->isEmpty())
                <div class="empty-state">
                    <p class="empty-state__title">Belum pernah terjual</p>
                    <p class="empty-state__text">Produk ini belum tercatat pada transaksi penjualan mana pun.</p>
                </div>
            @else
                <div class="tablelist__body">
                    <div class="tablelist__scroll">
                        <table class="tablelist__table">
                            <thead>
                                <tr>
                                    <th>Nomor</th>
                                    <th>Tanggal</th>
                                    <th class="kanan">Qty</th>
                                    <th class="kanan">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($penjualan as $baris)
                                    <tr>
                                        <td>
                                            <a class="tablelist__nomor" href="{{ route('sales.show', $baris->sale) }}">
                                                {{ $baris->sale->sale_number }}
                                            </a>
                                        </td>
                                        <td>{{ $baris->sale->sold_at->format('d/m/Y') }}</td>
                                        <td class="kanan">{{ $baris->quantity }}</td>
                                        <td class="kanan"><x-rupiah :value="$baris->subtotal" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </section>
    </div>
@endsection
