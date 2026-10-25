@extends('layouts.app')

@section('judul', 'Dashboard')

@section('content')
    @php
        $sapaan = auth()->user()->isAdmin() ? 'Owner' : 'Staff';
    @endphp

    <x-page-title
        title="Dashboard"
        subtitle="Ringkasan operasional RuangStok per {{ now()->format('d/m/Y') }} — seluruh angka diambil langsung dari database.">
        <x-slot:actions>
            <a href="{{ route('sales.create') }}" class="btn btn--utama">
                <x-icon name="plus" /> Catat Penjualan
            </a>
            <a href="{{ route('purchases.create') }}" class="btn btn--garis">
                <x-icon name="pembelian" /> Catat Pembelian
            </a>
        </x-slot:actions>
    </x-page-title>

    <div class="alert alert--info">
        <x-icon name="info" />
        <span>
            Halo, {{ auth()->user()->name }} ({{ $sapaan }}). Anda login sebagai
            {{ auth()->user()->isAdmin() ? 'Admin/Owner — dapat mengelola master data, akun, dan membatalkan transaksi'
                                        : 'Staff — dapat membuat pembelian dan penjualan' }}.
        </span>
    </div>

    <section aria-label="Ringkasan utama">
        <div class="stat-grid">
            <x-stat-card icon="produk" title="Total Produk"
                         :value="$ringkasan['produk'] . ' produk'"
                         :badge="$ringkasan['stok_rendah'] > 0 ? $ringkasan['stok_rendah'] . ' stok rendah' : null"
                         :badge-tipe="$ringkasan['stok_rendah'] > 0 ? 'peringatan' : 'biru'" />
            <x-stat-card icon="penjualan" title="Penjualan Bulan Ini"
                         :value="'Rp ' . number_format($ringkasan['nilai_penjualan_bulan'], 0, ',', '.')" />
            <x-stat-card icon="pembelian" title="Pembelian Bulan Ini"
                         :value="'Rp ' . number_format($ringkasan['nilai_pembelian_bulan'], 0, ',', '.')" />
            <x-stat-card icon="jam" title="Transaksi Tercatat"
                         :value="$ringkasan['pembelian'] + $ringkasan['penjualan'] . ' transaksi'"
                         :badge="($ringkasan['pembelian'] + $ringkasan['penjualan']) > 0 ? null : 'belum ada'"
                         badge-tipe="biru" />
        </div>
    </section>

    <section aria-label="Ringkasan operasional">
        <div class="stat-grid">
            <x-stat-card icon="penjualan" title="Penjualan Hari Ini"
                         :value="$ringkasan['penjualan_hari_ini'] . ' transaksi'" />
            <x-stat-card icon="kategori" title="Kategori Produk"
                         :value="\App\Models\Category::count() . ' kategori'" />
            <x-stat-card icon="pemasok" title="Pemasok Terdaftar"
                         :value="$ringkasan['supplier'] . ' pemasok'" />
            <x-stat-card icon="pengguna" title="Akun Pengguna"
                         :value="$ringkasan['pengguna'] . ' akun'" />
        </div>
    </section>

    <div class="dashboard-dua-kolom">
        <section class="card" aria-label="Transaksi terbaru">
            <div class="card__header">
                <h2 class="card__title">Transaksi Terbaru</h2>
                <div style="display:flex; gap:8px">
                    <a href="{{ route('purchases.index') }}" class="btn btn--netral btn--kecil">Pembelian</a>
                    <a href="{{ route('sales.index') }}" class="btn btn--netral btn--kecil">Penjualan</a>
                </div>
            </div>

            @if ($terbaru->isEmpty())
                <div class="empty-state">
                    <span class="empty-state__icon"><x-icon name="penjualan" :size="20" /></span>
                    <p class="empty-state__title">Belum ada transaksi</p>
                    <p class="empty-state__text">Mulai dengan mencatat pembelian untuk menambah stok, lalu penjualan untuk menguranginya.</p>
                    <div class="empty-state__aksi">
                        <a href="{{ route('purchases.create') }}" class="btn btn--utama"><x-icon name="plus" /> Catat Pembelian</a>
                    </div>
                </div>
            @else
                <div class="tablelist__body">
                    <div class="tablelist__scroll">
                        <table class="tablelist__table">
                            <thead>
                                <tr>
                                    <th>Nomor</th>
                                    <th>Jenis</th>
                                    <th>Pihak</th>
                                    <th>Waktu</th>
                                    <th class="kanan">Total</th>
                                    <th class="tengah">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($terbaru as $baris)
                                    <tr>
                                        <td>
                                            <a class="tablelist__nomor" href="{{ $baris['url'] }}">{{ $baris['nomor'] }}</a>
                                        </td>
                                        <td>{{ $baris['jenis'] }}</td>
                                        <td class="potong">{{ $baris['pihak'] }}</td>
                                        <td>{{ $baris['waktu']->format('d/m/Y H:i') }}</td>
                                        <td class="kanan"><x-rupiah :value="$baris['total']" /></td>
                                        <td class="tengah">
                                            @if ($baris['status'] === 'completed')
                                                <span class="badge badge--hijau">Selesai</span>
                                            @else
                                                <span class="badge badge--abu">Dibatalkan</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </section>

        <section class="card" aria-label="Produk stok rendah">
            <div class="card__header">
                <h2 class="card__title">Stok Rendah</h2>
                <a href="{{ route('products.low-stock') }}" class="btn btn--netral btn--kecil">Lihat semua</a>
            </div>

            @if ($stokRendah->isEmpty())
                <div class="empty-state">
                    <span class="empty-state__icon"><x-icon name="centang" :size="20" /></span>
                    <p class="empty-state__title">Semua stok aman</p>
                    <p class="empty-state__text">Tidak ada produk yang berada di bawah batas stok minimum.</p>
                </div>
            @else
                <ul style="list-style:none; margin:0; padding:8px 12px; display:flex; flex-direction:column; gap:8px">
                    @foreach ($stokRendah as $produk)
                        <li style="display:flex; align-items:center; justify-content:space-between; gap:10px; padding:8px 4px; border-bottom:1px solid var(--app-border)">
                            <a href="{{ route('products.show', $produk) }}" style="text-decoration:none; min-width:0">
                                <span style="display:block; font-size:13px; font-weight:600; color:var(--app-text)">{{ $produk->name }}</span>
                                <span style="font-size:11px; color:var(--app-text-muted)">{{ $produk->sku }} · min. {{ $produk->minimum_stock }}</span>
                            </a>
                            <span class="badge badge--merah">{{ $produk->stock }} {{ $produk->unit }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
@endsection
