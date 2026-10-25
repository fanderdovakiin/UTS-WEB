@extends('layouts.app')

@section('judul', 'Pemasok')

@section('content')
    <x-page-title title="Daftar Pemasok"
                  subtitle="Mitra pemasok barang. Staff dapat melihat daftar ini untuk form pembelian.">
        <x-slot:actions>
            @can('manage-master')
                <a href="{{ route('suppliers.create') }}" class="btn btn--utama">
                    <x-icon name="plus" /> Tambah Pemasok
                </a>
            @endcan
        </x-slot:actions>
    </x-page-title>

    <div class="tablelist">
        <form method="GET" action="{{ route('suppliers.index') }}" class="tablelist__toolbar">
            <div class="search-box">
                <x-icon name="cari" />
                <input type="search" name="q" value="{{ request('q') }}" class="input"
                       placeholder="Cari nama, telepon, atau email…" aria-label="Cari pemasok">
            </div>
            <div class="tablelist__filters">
                <button type="submit" class="btn btn--netral">Cari</button>
                @if (request('q'))
                    <a href="{{ route('suppliers.index') }}" class="btn btn--garis">Atur ulang</a>
                @endif
            </div>
        </form>

        <div class="tablelist__body">
            @if ($suppliers->isEmpty())
                <div class="empty-state">
                    <span class="empty-state__icon"><x-icon name="pemasok" :size="20" /></span>
                    <p class="empty-state__title">{{ request('q') ? 'Pemasok tidak ditemukan' : 'Belum ada pemasok' }}</p>
                    <p class="empty-state__text">
                        {{ request('q') ? 'Tidak ada pemasok yang cocok dengan pencarian.'
                                        : 'Tambahkan pemasok agar transaksi pembelian bisa dicatat.' }}
                    </p>
                    @can('manage-master')
                        @unless (request('q'))
                            <div class="empty-state__aksi">
                                <a href="{{ route('suppliers.create') }}" class="btn btn--utama">
                                    <x-icon name="plus" /> Tambah Pemasok
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
                                <th>Nama Pemasok</th>
                                <th style="width:150px">Telepon</th>
                                <th style="width:210px">Email</th>
                                <th class="kanan" style="width:110px">Pembelian</th>
                                <th class="tengah" style="width:170px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($suppliers as $supplier)
                                <tr>
                                    <td class="tablelist__nomor">{{ $supplier->name }}</td>
                                    <td style="color:var(--app-text-muted)">{{ $supplier->phone ?: '—' }}</td>
                                    <td style="color:var(--app-text-muted)">{{ $supplier->email ?: '—' }}</td>
                                    <td class="kanan">
                                        <span class="badge badge--biru">{{ $supplier->purchases_count }}</span>
                                    </td>
                                    <td class="tengah">
                                        <div class="tablelist__aksi">
                                            <a href="{{ route('suppliers.show', $supplier) }}" class="btn btn--garis btn--kecil">
                                                Detail
                                            </a>
                                            @can('manage-master')
                                                <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn--netral btn--kecil">
                                                    <x-icon name="ubah" :size="14" />
                                                </a>
                                                <form method="POST" action="{{ route('suppliers.destroy', $supplier) }}"
                                                      data-konfirmasi="Hapus pemasok &quot;{{ $supplier->name }}&quot;? Pemasok dengan riwayat pembelian tidak dapat dihapus.">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn--tipis btn--kecil">
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

        @if ($suppliers->hasPages())
            <div class="tablelist__footer">
                {{ $suppliers->links() }}
            </div>
        @endif
    </div>
@endsection
