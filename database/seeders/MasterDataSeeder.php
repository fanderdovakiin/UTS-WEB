<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    /**
     * Data master dummy. Memakai firstOrCreate: dijalankan ulang tidak menggandakan
     * data maupun mengubah stok yang sudah terpengaruh transaksi.
     */
    public function run(): void
    {
        $kategori = [
            ['name' => 'Elektronik', 'description' => 'Perangkat dan aksesori listrik.'],
            ['name' => 'Alat Tulis Kantor', 'description' => 'Kebutuhan kantor dan sekolah.'],
            ['name' => 'Makanan & Minuman', 'description' => 'Produk konsumsi harian.'],
            ['name' => 'Rumah Tangga', 'description' => 'Barang kebutuhan rumah.'],
            ['name' => 'Sepatu', 'description' => 'Alas kaki olahraga dan kasual.'],
        ];

        foreach ($kategori as $data) {
            Category::firstOrCreate(['name' => $data['name']], $data);
        }

        $suppliers = [
            ['name' => 'PT Sumber Elektronik Jaya', 'phone' => '0215550198', 'email' => 'sales@sumberelektronik.test', 'address' => 'Jl. Mangga Dua Raya No. 12, Jakarta'],
            ['name' => 'CV Karya Alat Tulis', 'phone' => '0227788123', 'email' => 'order@karyaatk.test', 'address' => 'Jl. Cihampelas No. 45, Bandung'],
            ['name' => 'UD Berkah Snack', 'phone' => '0314455667', 'email' => 'info@berkahsnack.test', 'address' => 'Jl. Rungkut Industri III No. 8, Surabaya'],
            ['name' => 'PT Bersih Sentosa', 'phone' => '0274556677', 'email' => 'cs@bersihsentosa.test', 'address' => 'Jl. Kaliurang KM 5, Yogyakarta'],
            ['name' => 'CV Langkah Sport', 'phone' => '0618899001', 'email' => 'hubungi@langkahsport.test', 'address' => 'Jl. Soekarno Hatta No. 99, Medan'],
        ];

        foreach ($suppliers as $data) {
            Supplier::firstOrCreate(['name' => $data['name']], $data);
        }

        $categories = Category::pluck('id', 'name');

        $products = [
            ['sku' => 'ELE-001', 'name' => 'Power Bank 10.000mAh', 'kategori' => 'Elektronik', 'purchase_price' => 120000, 'selling_price' => 185000, 'stock' => 24, 'minimum_stock' => 5, 'unit' => 'pcs'],
            ['sku' => 'ELE-002', 'name' => 'Kabel USB-C 1 Meter', 'kategori' => 'Elektronik', 'purchase_price' => 15000, 'selling_price' => 29000, 'stock' => 60, 'minimum_stock' => 15, 'unit' => 'pcs'],
            ['sku' => 'ELE-003', 'name' => 'TWS Earbuds Lite', 'kategori' => 'Elektronik', 'purchase_price' => 95000, 'selling_price' => 159000, 'stock' => 4, 'minimum_stock' => 6, 'unit' => 'pcs'],
            ['sku' => 'ELE-004', 'name' => 'Lampu LED 9W', 'kategori' => 'Elektronik', 'purchase_price' => 12000, 'selling_price' => 22000, 'stock' => 120, 'minimum_stock' => 20, 'unit' => 'pcs'],
            ['sku' => 'ATK-001', 'name' => 'Pulpen Gel Hitam 0.5', 'kategori' => 'Alat Tulis Kantor', 'purchase_price' => 2500, 'selling_price' => 4500, 'stock' => 200, 'minimum_stock' => 50, 'unit' => 'pcs'],
            ['sku' => 'ATK-002', 'name' => 'Buku Tulis A5 80 Lembar', 'kategori' => 'Alat Tulis Kantor', 'purchase_price' => 4500, 'selling_price' => 8000, 'stock' => 150, 'minimum_stock' => 40, 'unit' => 'pcs'],
            ['sku' => 'ATK-003', 'name' => 'Map Plastik A4', 'kategori' => 'Alat Tulis Kantor', 'purchase_price' => 3000, 'selling_price' => 6000, 'stock' => 18, 'minimum_stock' => 25, 'unit' => 'pcs'],
            ['sku' => 'SNK-001', 'name' => 'Kopi Bubuk Sachet 200gr', 'kategori' => 'Makanan & Minuman', 'purchase_price' => 18000, 'selling_price' => 27000, 'stock' => 80, 'minimum_stock' => 20, 'unit' => 'pack'],
            ['sku' => 'SNK-002', 'name' => 'Air Mineral 600ml (Dus)', 'kategori' => 'Makanan & Minuman', 'purchase_price' => 32000, 'selling_price' => 45000, 'stock' => 30, 'minimum_stock' => 10, 'unit' => 'dus'],
            ['sku' => 'RUM-001', 'name' => 'Sabun Cuci Piring 800ml', 'kategori' => 'Rumah Tangga', 'purchase_price' => 14000, 'selling_price' => 23000, 'stock' => 3, 'minimum_stock' => 8, 'unit' => 'pcs'],
            ['sku' => 'RUM-002', 'name' => 'Tisu Wajah 250 Lembar', 'kategori' => 'Rumah Tangga', 'purchase_price' => 9000, 'selling_price' => 16000, 'stock' => 45, 'minimum_stock' => 15, 'unit' => 'pack'],
            ['sku' => 'SEP-001', 'name' => 'Sepatu Sport Lokal Run', 'kategori' => 'Sepatu', 'purchase_price' => 145000, 'selling_price' => 225000, 'stock' => 12, 'minimum_stock' => 4, 'unit' => 'pcs'],
        ];

        foreach ($products as $data) {
            $kategori = $data['kategori'];
            unset($data['kategori']);

            Product::firstOrCreate(['sku' => $data['sku']], $data + [
                'category_id' => $categories[$kategori],
            ]);
        }
    }
}
