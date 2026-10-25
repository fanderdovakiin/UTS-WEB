@extends('layouts.app')

@section('judul', 'Catat Pembelian')

@section('content')
    <x-page-title title="Catat Pembelian"
                  subtitle="Pilih pemasok, lalu tambahkan satu atau lebih produk. Biaya per unit diambil dari harga beli produk di database." />

    <form method="POST" action="{{ route('purchases.store') }}" class="form"
          data-submit-once data-busy="Menyimpan…" data-form-transaksi data-harga-mode="beli">
        @csrf

        <div class="card">
            <div class="card__header">
                <h2 class="card__title">Informasi Transaksi</h2>
            </div>
            <div class="card__body">
                <div class="form-grid">
                    <div class="field">
                        <label class="field__label" for="supplier_id">Pemasok<span class="field__req">*</span></label>
                        <select id="supplier_id" name="supplier_id" class="select" required
                                aria-invalid="{{ $errors->has('supplier_id') ? 'true' : 'false' }}">
                            <option value="">— pilih pemasok —</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}"
                                        @selected((string) old('supplier_id') === (string) $supplier->id)>
                                    {{ $supplier->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('supplier_id')
                            <p class="field__error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="field">
                        <label class="field__label" for="purchased_at">Tanggal pembelian</label>
                        <input id="purchased_at" name="purchased_at" type="date" class="input"
                               value="{{ old('purchased_at', now()->format('Y-m-d')) }}">
                        <p class="field__hint">Kosongkan untuk memakai waktu sekarang.</p>
                        @error('purchased_at')
                            <p class="field__error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="field">
                        <label class="field__label" for="note">Catatan</label>
                        <input id="note" name="note" type="text" class="input" maxlength="255"
                               value="{{ old('note') }}" placeholder="Opsional, mis. No. surat jalan">
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card__header">
                <h2 class="card__title">Item Pembelian</h2>
            </div>
            <div class="card__body">
                @if ($errors->has('items') || collect($errors->messages())->keys()->contains(fn ($k) => str_starts_with($k, 'items.')))
                    <div class="alert alert--galat" role="alert" style="margin-bottom:12px">
                        <x-icon name="peringatan" />
                        <div>
                            <strong>Daftar item perlu diperbaiki:</strong>
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
                        Harga biaya per unit mengikuti harga beli produk — tidak bisa dimanipulasi dari browser.
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
                            'mode' => 'beli',
                        ])
                    @endforeach
                </div>

                <div class="ringkasan-transaksi" style="margin-top:14px">
                    <div>
                        <div style="font-size:11.5px; color:var(--app-text-muted)">
                            Total item: <strong data-total-item>0</strong> unit
                        </div>
                        <div class="ringkasan-transaksi__total" data-total-transaksi>Rp 0</div>
                    </div>
                    <div class="ringkasan-transaksi__catatan" style="max-width:340px; text-align:right">
                        Angka di atas hanya perkiraan untuk kenyamanan. Nilai final dihitung ulang
                        oleh server dari database saat disimpan.
                    </div>
                </div>
            </div>
        </div>

        <div class="form-aksi">
            <a href="{{ route('purchases.index') }}" class="btn btn--garis">Batal</a>
            <button type="submit" class="btn btn--utama">Simpan Pembelian</button>
        </div>
    </form>

    {{-- Template baris item: dirender ulang oleh JS saat "Tambah Item" ditekan. --}}
    <template id="item-row-template">
        @include('transactions.item-row', [
            'index' => '__INDEX__',
            'produk' => ['product_id' => '', 'quantity' => 1],
            'daftarProduk' => $products,
            'mode' => 'beli',
        ])
    </template>
@endsection
