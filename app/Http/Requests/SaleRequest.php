<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'note' => ['nullable', 'string', 'max:255'],
            'sold_at' => ['nullable', 'date'],
            // Harga jual & total tidak dipercaya dari browser; dihitung ulang oleh SaleService.
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'items' => 'daftar item',
            'items.*.product_id' => 'produk',
            'items.*.quantity' => 'kuantitas',
            'sold_at' => 'tanggal penjualan',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Transaksi minimal harus memiliki satu item produk.',
            'items.min' => 'Transaksi minimal harus memiliki satu item produk.',
            'items.*.product_id.required' => 'Setiap baris wajib memilih produk.',
            'items.*.product_id.exists' => 'Ada produk yang tidak ditemukan, periksa kembali daftar item.',
            'items.*.quantity.required' => 'Kuantitas wajib diisi.',
            'items.*.quantity.integer' => 'Kuantitas harus berupa bilangan bulat.',
            'items.*.quantity.min' => 'Kuantitas minimal 1.',
            'sold_at.date' => 'Tanggal penjualan tidak valid.',
        ];
    }
}
