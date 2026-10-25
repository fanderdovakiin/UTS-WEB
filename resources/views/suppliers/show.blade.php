@extends('layouts.app')

@section('judul', 'Detail Pemasok')

@section('content')
    <x-page-title :title="$supplier->name" subtitle="Riwayat pembelian dari pemasok ini.">
        <x-slot:actions>
            <a href="{{ route('suppliers.index') }}" class="btn btn--garis">Kembali</a>
            @can('manage-master')
                <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn--utama">
                    <x-icon name="ubah" /> Ubah Pemasok
                </a>
            @endcan
        </x-slot:actions>
    </x-page-title>

    <div class="card">
        <div class="card__header">
            <h2 class="card__title">Kontak</h2>
            <span class="badge badge--biru">{{ $supplier->purchases->count() }} pembelian terakhir</span>
        </div>
        <div class="card__body">
            <div class="detail-grid">
                <div>
                    <span class="detail-grid__label">Nama</span>
                    <p class="detail-grid__value">{{ $supplier->name }}</p>
                </div>
                <div>
                    <span class="detail-grid__label">Telepon</span>
                    <p class="detail-grid__value">{{ $supplier->phone ?: '—' }}</p>
                </div>
                <div>
                    <span class="detail-grid__label">Email</span>
                    <p class="detail-grid__value">{{ $supplier->email ?: '—' }}</p>
                </div>
                <div>
                    <span class="detail-grid__label">Alamat</span>
                    <p class="detail-grid__value">{{ $supplier->address ?: '—' }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card__header">
            <h2 class="card__title">Pembelian Terakhir</h2>
            <a href="{{ route('purchases.index', ['q' => $supplier->name]) }}" class="btn btn--netral btn--kecil">
                Lihat semua
            </a>
        </div>

        @if ($supplier->purchases->isEmpty())
            <div class="empty-state">
                <p class="empty-state__title">Belum ada pembelian</p>
                <p class="empty-state__text">Pemasok ini belum memiliki riwayat transaksi pembelian.</p>
            </div>
        @else
            <div class="tablelist__body">
                <div class="tablelist__scroll">
                    <table class="tablelist__table">
                        <thead>
                            <tr>
                                <th>Nomor</th>
                                <th>Tanggal</th>
                                <th>Pencatat</th>
                                <th class="kanan">Total</th>
                                <th class="tengah">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($supplier->purchases as $purchase)
                                <tr>
                                    <td>
                                        <a class="tablelist__nomor" href="{{ route('purchases.show', $purchase) }}">
                                            {{ $purchase->purchase_number }}
                                        </a>
                                    </td>
                                    <td>{{ $purchase->purchased_at->format('d/m/Y H:i') }}</td>
                                    <td style="color:var(--app-text-muted)">{{ $purchase->user->name }}</td>
                                    <td class="kanan"><x-rupiah :value="$purchase->total" /></td>
                                    <td class="tengah">
                                        @if ($purchase->isCompleted())
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
    </div>
@endsection
