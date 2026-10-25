@php
    // $index bisa berupa angka (old input) atau penanda template '__INDEX__'.
    $nilaiProduk = is_array($produk) ? ($produk['product_id'] ?? '') : '';
    $nilaiQty = is_array($produk) ? ($produk['quantity'] ?? 1) : 1;
    $modeBeli = $mode === 'beli';
@endphp

<div class="item-row">
    <div class="field">
        <label class="field__label">
            <span data-nomor-baris>Item</span> · Produk<span class="field__req">*</span>
        </label>
        <select name="items[{{ $index }}][product_id]" class="select" data-produk required>
            <option value="">— pilih produk —</option>
            @foreach ($daftarProduk as $p)
                <option value="{{ $p->id }}"
                        data-harga="{{ $modeBeli ? $p->purchase_price : $p->selling_price }}"
                        data-stok="{{ $p->stock }}"
                        data-satuan="{{ $p->unit }}"
                        @selected((string) $nilaiProduk === (string) $p->id)>
                    {{ $p->sku }} — {{ $p->name }} ({{ $modeBeli ? 'beli' : 'jual' }} Rp
                    {{ number_format((float) ($modeBeli ? $p->purchase_price : $p->selling_price), 0, ',', '.') }})
                </option>
            @endforeach
        </select>
    </div>

    <div class="field">
        <label class="field__label">Kuantitas<span class="field__req">*</span></label>
        <input type="number" name="items[{{ $index }}][quantity]" class="input"
               min="1" step="1" value="{{ $nilaiQty }}" data-kuantitas required
               inputmode="numeric">
    </div>

    <div class="item-row__subtotal" data-subtotal>Rp 0</div>

    <div class="item-row__hapus">
        <button type="button" class="btn btn--tipis btn--ikon" data-hapus-baris
                title="Hapus item ini" aria-label="Hapus item ini">
            <x-icon name="tutup" :size="14" />
        </button>
    </div>

    <div class="item-row__stok" data-info></div>
</div>
