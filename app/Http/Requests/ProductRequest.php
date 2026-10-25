<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $product = $this->route('product');
        $dibuat = $this->isMethod('POST');

        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'sku' => [
                'required',
                'string',
                'max:40',
                'regex:/^[A-Za-z0-9\-_\/]+$/',
                Rule::unique('products', 'sku')->ignore($product),
            ],
            'name' => ['required', 'string', 'max:150'],
            'purchase_price' => ['required', 'numeric', 'min:0', 'max:999999999999'],
            'selling_price' => ['required', 'numeric', 'min:0', 'max:999999999999'],
            'minimum_stock' => ['required', 'integer', 'min:0'],
            'unit' => ['required', 'string', 'max:20'],
            // Stok awal hanya diisi saat pembuatan; perubahan berikutnya lewat transaksi.
            'stock' => $dibuat ? ['required', 'integer', 'min:0'] : ['sometimes', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required' => 'Kategori wajib dipilih.',
            'category_id.exists' => 'Kategori yang dipilih tidak ditemukan.',
            'sku.required' => 'SKU wajib diisi.',
            'sku.unique' => 'SKU sudah digunakan oleh produk lain.',
            'sku.regex' => 'SKU hanya boleh berisi huruf, angka, tanda hubung, garis miring, atau garis bawah.',
            'sku.max' => 'SKU maksimal 40 karakter.',
            'name.required' => 'Nama produk wajib diisi.',
            'name.max' => 'Nama produk maksimal 150 karakter.',
            'purchase_price.required' => 'Harga beli wajib diisi.',
            'purchase_price.numeric' => 'Harga beli harus berupa angka.',
            'purchase_price.min' => 'Harga beli tidak boleh negatif.',
            'selling_price.required' => 'Harga jual wajib diisi.',
            'selling_price.numeric' => 'Harga jual harus berupa angka.',
            'selling_price.min' => 'Harga jual tidak boleh negatif.',
            'stock.required' => 'Stok awal wajib diisi.',
            'stock.integer' => 'Stok harus berupa bilangan bulat.',
            'stock.min' => 'Stok tidak boleh negatif.',
            'minimum_stock.required' => 'Stok minimum wajib diisi.',
            'minimum_stock.integer' => 'Stok minimum harus berupa bilangan bulat.',
            'minimum_stock.min' => 'Stok minimum tidak boleh negatif.',
            'unit.required' => 'Satuan wajib dipilih.',
        ];
    }
}
