@extends('layouts.app')

@section('judul', 'Pembelian')

@section('content')
    <x-page-title title="Pembelian (Stock-in)"
                  subtitle="Transaksi pembelian menambah stok produk. Total dihitung oleh server dari harga beli produk.">
        <x-slot:actions>
            <a href="{{ route('purchases.create') }}" class="btn btn--utama">
                <x-icon name="plus" /> Catat Pembelian
            </a>
        </x-slot:actions>
    </x-page-title>

    <div class="tablelist">
        <form method="GET" action="{{ route('purchases.index') }}" class="tablelist__toolbar">
            <div class="search-box">
                <x-icon name="cari" />
                <input type="search" name="q" value="{{ request('q') }}" class="input"
                       placeholder="Cari nomor transaksi atau pemasok…" aria-label="Cari pembelian">
            </div>

            <div class="tablelist__filters">
                <select name="status" class="select" aria-label="Saring status">
                    <option value="">Semua status</option>
                    <option value="completed" @selected(request('status') === 'completed')>Selesai</option>
                    <option value="cancelled" @selected(request('status') === 'cancelled')>Dibatalkan</option>
                </select>

                <input type="date" name="dari" value="{{ request('dari') }}" class="input"
                       aria-label="Tanggal mulai" title="Tanggal mulai">

                <input type="date" name="sampai" value="{{ request('sampai') }}" class="input"
                       aria-label="Tanggal akhir" title="Tanggal akhir">

                <button type="submit" class="btn btn--netral">Terapkan</button>

                @if (request()->hasAny(['q', 'status', 'dari', 'sampai']))
                    <a href="{{ route('purchases.index') }}" class="btn btn--garis">Atur ulang</a>
                @endif
            </div>
        </form>

        <div class="tablelist__body">
            @if ($purchases->isEmpty())
                <div class="empty-state">
                    <span class="empty-state__icon"><x-icon name="pembelian" :size="20" /></span>
                    <p class="empty-state__title">Belum ada pembelian</p>
                    <p class="empty-state__text">
                        Catat pembelian untuk menambah stok. Anda bisa memasukkan beberapa produk
                        dalam satu transaksi.
                    </p>
                    <div class="empty-state__aksi">
                        <a href="{{ route('purchases.create') }}" class="btn btn--utama">
                            <x-icon name="plus" /> Catat Pembelian
                        </a>
                    </div>
                </div>
            @else
                <div class="tablelist__scroll">
                    <table class="tablelist__table">
                        <thead>
                            <tr>
                                <th style="width:170px">Nomor</th>
                                <th style="width:140px">Tanggal</th>
                                <th>Pemasok</th>
                                <th style="width:160px">Pencatat</th>
                                <th class="kanan" style="width:150px">Total</th>
                                <th class="tengah" style="width:130px">Status</th>
                                <th class="tengah" style="width:90px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($purchases as $purchase)
                                <tr>
                                    <td>
                                        <a class="tablelist__nomor" href="{{ route('purchases.show', $purchase) }}">
                                            {{ $purchase->purchase_number }}
                                        </a>
                                    </td>
                                    <td>{{ $purchase->purchased_at->format('d/m/Y H:i') }}</td>
                                    <td class="potong">{{ $purchase->supplier->name }}</td>
                                    <td style="color:var(--app-text-muted)">{{ $purchase->user->name }}</td>
                                    <td class="kanan"><x-rupiah :value="$purchase->total" /></td>
                                    <td class="tengah">
                                        @if ($purchase->isCompleted())
                                            <span class="badge badge--hijau">Selesai</span>
                                        @else
                                            <span class="badge badge--abu">Dibatalkan</span>
                                        @endif
                                    </td>
                                    <td class="tengah">
                                        <a href="{{ route('purchases.show', $purchase) }}" class="btn btn--garis btn--kecil">
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

        @if ($purchases->hasPages())
            <div class="tablelist__footer">
                {{ $purchases->links() }}
            </div>
        @endif
    </div>
@endsection
