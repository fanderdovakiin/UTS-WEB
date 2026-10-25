@extends('layouts.app')

@section('judul', 'Penjualan')

@section('content')
    <x-page-title title="Penjualan (Stock-out)"
                  subtitle="Transaksi penjualan mengurangi stok. Server menolak transaksi bila stok tidak mencukupi.">
        <x-slot:actions>
            <a href="{{ route('sales.create') }}" class="btn btn--utama">
                <x-icon name="plus" /> Catat Penjualan
            </a>
        </x-slot:actions>
    </x-page-title>

    <div class="tablelist">
        <form method="GET" action="{{ route('sales.index') }}" class="tablelist__toolbar">
            <div class="search-box">
                <x-icon name="cari" />
                <input type="search" name="q" value="{{ request('q') }}" class="input"
                       placeholder="Cari nomor transaksi atau petugas…" aria-label="Cari penjualan">
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
                    <a href="{{ route('sales.index') }}" class="btn btn--garis">Atur ulang</a>
                @endif
            </div>
        </form>

        <div class="tablelist__body">
            @if ($sales->isEmpty())
                <div class="empty-state">
                    <span class="empty-state__icon"><x-icon name="penjualan" :size="20" /></span>
                    <p class="empty-state__title">Belum ada penjualan</p>
                    <p class="empty-state__text">
                        Catat penjualan pertama Anda. Stok akan berkurang otomatis bila seluruh
                        item memenuhi syarat.
                    </p>
                    <div class="empty-state__aksi">
                        <a href="{{ route('sales.create') }}" class="btn btn--utama">
                            <x-icon name="plus" /> Catat Penjualan
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
                                <th>Petugas</th>
                                <th class="kanan" style="width:90px">Item</th>
                                <th class="kanan" style="width:150px">Total</th>
                                <th class="tengah" style="width:130px">Status</th>
                                <th class="tengah" style="width:90px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sales as $sale)
                                <tr>
                                    <td>
                                        <a class="tablelist__nomor" href="{{ route('sales.show', $sale) }}">
                                            {{ $sale->sale_number }}
                                        </a>
                                    </td>
                                    <td>{{ $sale->sold_at->format('d/m/Y H:i') }}</td>
                                    <td class="potong">{{ $sale->user->name }}</td>
                                    <td class="kanan">{{ $sale->items()->sum('quantity') }}</td>
                                    <td class="kanan"><x-rupiah :value="$sale->total" /></td>
                                    <td class="tengah">
                                        @if ($sale->isCompleted())
                                            <span class="badge badge--hijau">Selesai</span>
                                        @else
                                            <span class="badge badge--abu">Dibatalkan</span>
                                        @endif
                                    </td>
                                    <td class="tengah">
                                        <a href="{{ route('sales.show', $sale) }}" class="btn btn--garis btn--kecil">
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

        @if ($sales->hasPages())
            <div class="tablelist__footer">
                {{ $sales->links() }}
            </div>
        @endif
    </div>
@endsection
