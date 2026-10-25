<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'note' => ['nullable', 'string', 'max:255'],
            'purchased_at' => ['nullable', 'date'],
            // Harga tidak divalidasi di sini karena tidak diterima dari browser:
            // unit cost diambil dari products.purchase_price oleh PurchaseService.
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'supplier_id' => 'pemasok',
            'items' => 'daftar item',
            'items.*.product_id' => 'produk',
            'items.*.quantity' => 'kuantitas',
            'purchased_at' => 'tanggal pembelian',
        ];
    }

    public function messages(): array
    {
        return [
            'supplier_id.required' => 'Pemasok wajib dipilih.',
            'supplier_id.exists' => 'Pemasok yang dipilih tidak ditemukan.',
            'items.required' => 'Transaksi minimal harus memiliki satu item produk.',
            'items.min' => 'Transaksi minimal harus memiliki satu item produk.',
            'items.*.product_id.required' => 'Setiap baris wajib memilih produk.',
            'items.*.product_id.exists' => 'Ada produk yang tidak ditemukan, periksa kembali daftar item.',
            'items.*.quantity.required' => 'Kuantitas wajib diisi.',
            'items.*.quantity.integer' => 'Kuantitas harus berupa bilangan bulat.',
            'items.*.quantity.min' => 'Kuantitas minimal 1.',
            'purchased_at.date' => 'Tanggal pembelian tidak valid.',
        ];
    }
}
