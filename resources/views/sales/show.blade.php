@extends('layouts.app')

@section('judul', 'Detail Penjualan')

@section('content')
    <x-page-title :title="'Penjualan ' . $sale->sale_number"
                  :subtitle="'Dicatat oleh ' . $sale->user->name . ' · ' . $sale->sold_at->format('d/m/Y H:i')">
        <x-slot:actions>
            <a href="{{ route('sales.index') }}" class="btn btn--garis">Kembali</a>

            @if ($sale->isCompleted())
                @can('cancel-transaction')
                    <form method="POST" action="{{ route('sales.cancel', $sale) }}"
                          data-konfirmasi="Batalkan penjualan {{ $sale->sale_number }}? Stok produk akan dikembalikan sebesar kuantitas yang terjual, dan aksi ini tidak bisa diulang.">
                        @csrf
                        <button type="submit" class="btn btn--tipis" data-busy="Membatalkan…">
                            <x-icon name="tutup" /> Batalkan Transaksi
                        </button>
                    </form>
                @endcan
            @else
                <span class="badge badge--abu">Sudah dibatalkan — stok sudah dikembalikan</span>
            @endif
        </x-slot:actions>
    </x-page-title>

    @unless ($sale->isCompleted())
        <div class="alert alert--info">
            <x-icon name="info" />
            <span>
                Transaksi ini sudah <strong>dibatalkan</strong>. Stok produk telah dikembalikan
                sebesar kuantitas yang terjual, dan barisnya tetap disimpan sebagai riwayat.
            </span>
        </div>
    @endunless

    <div class="card">
        <div class="card__header">
            <h2 class="card__title">Informasi Transaksi</h2>
            @if ($sale->isCompleted())
                <span class="badge badge--hijau">Selesai</span>
            @else
                <span class="badge badge--abu">Dibatalkan</span>
            @endif
        </div>
        <div class="card__body">
            <div class="detail-grid">
                <div>
                    <span class="detail-grid__label">Nomor penjualan</span>
                    <p class="detail-grid__value">{{ $sale->sale_number }}</p>
                </div>
                <div>
                    <span class="detail-grid__label">Pencatat</span>
                    <p class="detail-grid__value">{{ $sale->user->name }}</p>
                </div>
                <div>
                    <span class="detail-grid__label">Tanggal</span>
                    <p class="detail-grid__value">{{ $sale->sold_at->format('d/m/Y H:i') }}</p>
                </div>
                <div>
                    <span class="detail-grid__label">Jumlah item</span>
                    <p class="detail-grid__value">{{ $sale->items->sum('quantity') }} unit</p>
                </div>
                <div>
                    <span class="detail-grid__label">Total</span>
                    <p class="detail-grid__value" style="font-weight:700"><x-rupiah :value="$sale->total" /></p>
                </div>
                @if ($sale->note)
                    <div>
                        <span class="detail-grid__label">Catatan</span>
                        <p class="detail-grid__value">{{ $sale->note }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card__header">
            <h2 class="card__title">Item Penjualan</h2>
        </div>
        <div class="tablelist__body">
            <div class="tablelist__scroll">
                <table class="tablelist__table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th style="width:140px">SKU</th>
                            <th class="kanan" style="width:90px">Qty</th>
                            <th class="kanan" style="width:150px">Harga/Unit</th>
                            <th class="kanan" style="width:160px">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sale->items as $item)
                            <tr>
                                <td class="potong">
                                    <a href="{{ route('products.show', $item->product) }}" style="text-decoration:none">
                                        {{ $item->product->name }}
                                    </a>
                                </td>
                                <td style="color:var(--app-text-muted)">{{ $item->product->sku }}</td>
                                <td class="kanan">{{ $item->quantity }}</td>
                                <td class="kanan"><x-rupiah :value="$item->unit_price" /></td>
                                <td class="kanan"><x-rupiah :value="$item->subtotal" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" class="kanan" style="font-weight:700; background:var(--app-fill-strong)">
                                Total
                            </td>
                            <td class="kanan" style="font-weight:700; background:var(--app-fill-strong)">
                                <x-rupiah :value="$sale->total" />
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
@endsection
