<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;
use App\Services\PurchaseService;
use App\Services\SaleService;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    /**
     * Contoh pembelian dan penjualan yang dibuat lewat service (sehingga stok,
     * subtotal, dan total selalu konsisten). Dijaga agar tidak menggandakan
     * transaksi bila seeder dijalankan ulang.
     */
    public function run(): void
    {
        if (Purchase::count() > 0 || Sale::count() > 0) {
            return;
        }

        $admin = User::where('role', User::ROLE_ADMIN)->first() ?? User::firstOrFail();
        $staff = User::where('role', User::ROLE_STAFF)->first() ?? $admin;

        $pembelian = app(PurchaseService::class);
        $penjualan = app(SaleService::class);

        $contohPembelian = [
            ['pemasok' => 'PT Sumber Elektronik Jaya', 'hari' => 18, 'user' => $staff, 'item' => [['ELE-001', 10], ['ELE-004', 30], ['ELE-002', 40]]],
            ['pemasok' => 'CV Karya Alat Tulis', 'hari' => 14, 'user' => $staff, 'item' => [['ATK-001', 50], ['ATK-002', 40]]],
            ['pemasok' => 'UD Berkah Snack', 'hari' => 9, 'user' => $admin, 'item' => [['SNK-001', 30], ['SNK-002', 15]]],
            ['pemasok' => 'PT Bersih Sentosa', 'hari' => 7, 'user' => $staff, 'item' => [['RUM-002', 20]]],
            ['pemasok' => 'CV Langkah Sport', 'hari' => 4, 'user' => $admin, 'item' => [['SEP-001', 8]]],
        ];

        foreach ($contohPembelian as $data) {
            $pembelian->create($data['user'], [
                'supplier_id' => Supplier::where('name', $data['pemasok'])->firstOrFail()->id,
                'note' => 'Pembelian contoh hasil seeder.',
                'purchased_at' => now()->subDays($data['hari'])->setTime(9, 30),
            ], $this->ubahSku($data['item']));
        }

        $contohPenjualan = [
            ['hari' => 16, 'user' => $staff, 'item' => [['ELE-001', 2], ['ELE-002', 5], ['ATK-001', 10]]],
            ['hari' => 12, 'user' => $staff, 'item' => [['SNK-001', 6], ['SNK-002', 4], ['RUM-002', 5]]],
            ['hari' => 8, 'user' => $admin, 'item' => [['ATK-002', 8], ['ELE-004', 12], ['SNK-001', 5]]],
            ['hari' => 5, 'user' => $staff, 'item' => [['SEP-001', 3], ['ELE-001', 4], ['ATK-001', 12]]],
            ['hari' => 2, 'user' => $admin, 'item' => [['ELE-002', 8], ['RUM-002', 6], ['SNK-002', 3]]],
            ['hari' => 1, 'user' => $staff, 'item' => [['ATK-002', 10], ['ELE-004', 15], ['SEP-001', 2]]],
        ];

        foreach ($contohPenjualan as $data) {
            $penjualan->create($data['user'], [
                'note' => 'Penjualan contoh hasil seeder.',
                'sold_at' => now()->subDays($data['hari'])->setTime(14, 15),
            ], $this->ubahSku($data['item']));
        }
    }

    /**
     * Ubah daftar [sku, kuantitas] menjadi format yang diterima service.
     *
     * @param  list<array{0:string, 1:int}>  $item
     * @return list<array{product_id:int, quantity:int}>
     */
    private function ubahSku(array $item): array
    {
        return collect($item)->map(fn ($row) => [
            'product_id' => Product::where('sku', $row[0])->firstOrFail()->id,
            'quantity' => $row[1],
        ])->all();
    }
}
