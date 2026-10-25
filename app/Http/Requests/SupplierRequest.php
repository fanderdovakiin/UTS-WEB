<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $supplier = $this->route('supplier');

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('suppliers', 'name')->ignore($supplier),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama pemasok wajib diisi.',
            'name.unique' => 'Nama pemasok sudah terdaftar.',
            'name.max' => 'Nama pemasok maksimal 100 karakter.',
            'phone.max' => 'Nomor telepon maksimal 30 karakter.',
            'email.email' => 'Format email pemasok tidak valid.',
            'email.max' => 'Email pemasok maksimal 100 karakter.',
            'address.max' => 'Alamat maksimal 255 karakter.',
        ];
    }
}
