@extends('layouts.app')

@section('judul', 'Catat Penjualan')

@section('content')
    <x-page-title title="Catat Penjualan"
                  subtitle="Tambahkan satu atau lebih produk. Harga jual dan total dihitung server; stok dicek ulang sebelum disimpan." />

    <form method="POST" action="{{ route('sales.store') }}" class="form"
          data-submit-once data-busy="Menyimpan…" data-form-transaksi data-harga-mode="jual">
        @csrf

        <div class="card">
            <div class="card__header">
                <h2 class="card__title">Informasi Transaksi</h2>
            </div>
            <div class="card__body">
                <div class="form-grid">
                    <div class="field">
                        <label class="field__label" for="sold_at">Tanggal penjualan</label>
                        <input id="sold_at" name="sold_at" type="date" class="input"
                               value="{{ old('sold_at', now()->format('Y-m-d')) }}">
                        <p class="field__hint">Kosongkan untuk memakai waktu sekarang.</p>
                        @error('sold_at')
                            <p class="field__error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="field">
                        <label class="field__label" for="note">Catatan</label>
                        <input id="note" name="note" type="text" class="input" maxlength="255"
                               value="{{ old('note') }}" placeholder="Opsional, mis. Nama pelanggan">
                        @error('note')
                            <p class="field__error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card__header">
                <h2 class="card__title">Item Penjualan</h2>
            </div>
            <div class="card__body">
                @if ($errors->has('items') || collect($errors->messages())->keys()->contains(fn ($k) => str_starts_with($k, 'items.')))
                    <div class="alert alert--galat" role="alert" style="margin-bottom:12px">
                        <x-icon name="peringatan" />
                        <div>
                            <strong>Transaksi tidak tersimpan — daftar item perlu diperbaiki:</strong>
                            <ul style="margin:6px 0 0; padding-left:18px">
                                @foreach ($errors->messages() as $kunci => $pesan)
                                    @if ($kunci === 'items' || str_starts_with($kunci, 'items.'))
                                        @foreach ($pesan as $satu)
                                            <li>{{ $satu }}</li>
                                        @endforeach
                                    @endif
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                <div class="item-toolbar" style="margin-bottom:10px">
                    <span style="font-size:12px; color:var(--app-text-muted)">
                        Stok tersedia ditampilkan pada setiap baris. Pemeriksaan stok final tetap
                        dilakukan server tepat sebelum transaksi disimpan.
                    </span>
                    <button type="button" class="btn btn--netral" data-tambah-baris>
                        <x-icon name="plus" /> Tambah Item
                    </button>
                </div>

                <div class="item-rows" data-daftar-baris>
                    @php
                        $barisLama = old('items');
                        if (!is_array($barisLama) || count($barisLama) === 0) {
                            $barisLama = [[
                                'product_id' => request('produk'),
                                'quantity' => 1,
                            ]];
                        }
                    @endphp

                    @foreach ($barisLama as $index => $baris)
                        @include('transactions.item-row', [
                            'index' => $index,
                            'produk' => $baris,
                            'daftarProduk' => $products,
                            'mode' => 'jual',
                        ])
                    @endforeach
                </div>

                <div class="ringkasan-transaksi" style="margin-top:14px">
                    <div>
                        <div style="font-size:11.5px; color:var(--app-text-muted)">
                            Total terjual: <strong data-total-item>0</strong> unit
                        </div>
                        <div class="ringkasan-transaksi__total" data-total-transaksi>Rp 0</div>
                    </div>
                    <div class="ringkasan-transaksi__catatan" style="max-width:340px; text-align:right">
                        Perkiraan untuk kenyamanan. Nilai final dihitung server dari harga jual
                        di database — angka yang dikirim browser tidak dipercaya.
                    </div>
                </div>
            </div>
        </div>

        <div class="form-aksi">
            <a href="{{ route('sales.index') }}" class="btn btn--garis">Batal</a>
            <button type="submit" class="btn btn--utama">Simpan Penjualan</button>
        </div>
    </form>

    <template id="item-row-template">
        @include('transactions.item-row', [
            'index' => '__INDEX__',
            'produk' => ['product_id' => '', 'quantity' => 1],
            'daftarProduk' => $products,
            'mode' => 'jual',
        ])
    </template>
@endsection
