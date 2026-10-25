<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatTransaksi;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use MembuatTransaksi, RefreshDatabase;

    public function test_dashboard_menampilkan_angka_asli_dari_database(): void
    {
        $admin = User::factory()->admin()->create();

        // 3 produk (1 stok rendah), 1 pemasok lewat pembelian, 1 penjualan hari ini.
        Product::factory()->count(2)->create(['stock' => 50, 'minimum_stock' => 5]);
        $rendah = Product::factory()->lowStock()->create();
        $this->buatPembelian($admin, [[$rendah, 10]]);
        $this->buatPenjualan($admin, [[$rendah, 3]]);
        $this->buatPenjualan($admin, [[$rendah, 2]]);

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $ringkasan = $response->viewData('ringkasan');

        $this->assertSame(Product::count(), $ringkasan['produk']);
        $this->assertSame(
            Product::whereColumn('stock', '<=', 'minimum_stock')->count(),
            $ringkasan['stok_rendah']
        );
        $this->assertSame(1, $ringkasan['pembelian']);
        $this->assertSame(2, $ringkasan['penjualan']);
        $this->assertSame(2, $ringkasan['penjualan_hari_ini']);
        $this->assertGreaterThan(0, $ringkasan['nilai_penjualan_bulan']);
        $this->assertGreaterThan(0, $ringkasan['nilai_pembelian_bulan']);
    }

    public function test_dashboard_kosong_tanpa_data(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $ringkasan = $response->viewData('ringkasan');

        $this->assertSame(0, $ringkasan['produk']);
        $this->assertSame(0, $ringkasan['penjualan']);
        $this->assertSame(0.0, $ringkasan['nilai_penjualan_bulan']);
        $this->assertCount(0, $response->viewData('stokRendah'));
    }

    public function test_dashboard_dihitung_dari_status_selesai_saja(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['stock' => 10]);
        $sale = $this->buatPenjualan($admin, [[$product, 2]]);

        // Nilai transaksi batal tidak boleh ikut dihitung.
        $this->actingAs($admin)->post(route('sales.cancel', $sale));

        $response = $this->actingAs($admin)->get(route('dashboard'));
        $ringkasan = $response->viewData('ringkasan');

        $this->assertSame(0, $ringkasan['penjualan_hari_ini']);
        $this->assertSame(0.0, $ringkasan['nilai_penjualan_bulan']);
    }
}
