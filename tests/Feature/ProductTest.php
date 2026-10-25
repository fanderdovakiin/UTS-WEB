<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatTransaksi;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use MembuatTransaksi, RefreshDatabase;

    private function payloadProduk(array $ubah = []): array
    {
        return array_merge([
            'category_id' => Category::factory()->create()->id,
            'sku' => 'SKU-TEST-001',
            'name' => 'Produk Uji',
            'purchase_price' => 10000,
            'selling_price' => 15000,
            'minimum_stock' => 5,
            'unit' => 'pcs',
            'stock' => 20,
        ], $ubah);
    }

    public function test_admin_dapat_menambah_produk(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->post(route('products.store'), $this->payloadProduk());

        $response->assertRedirect(route('products.index'));
        $this->assertDatabaseHas('products', ['sku' => 'SKU-TEST-001', 'stock' => 20]);
    }

    public function test_sku_tidak_boleh_duplikat(): void
    {
        $admin = User::factory()->admin()->create();
        Product::factory()->create(['sku' => 'SUDAH-ADA']);

        $response = $this->actingAs($admin)
            ->from(route('products.create'))
            ->post(route('products.store'), $this->payloadProduk(['sku' => 'SUDAH-ADA']));

        $response->assertRedirect(route('products.create'));
        $response->assertSessionHasErrors('sku');
        $this->assertStringContainsString(
            'SKU sudah digunakan',
            session('errors')->first('sku')
        );
        $this->assertSame(1, Product::where('sku', 'SUDAH-ADA')->count());
    }

    public function test_harga_dan_stok_negatif_ditolak(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->from(route('products.create'))
            ->post(route('products.store'), $this->payloadProduk([
                'purchase_price' => -1000,
                'selling_price' => -500,
                'stock' => -3,
                'minimum_stock' => -1,
            ]));

        $response->assertSessionHasErrors([
            'purchase_price',
            'selling_price',
            'stock',
            'minimum_stock',
        ]);
        $this->assertDatabaseMissing('products', ['sku' => 'SKU-TEST-001']);
    }

    public function test_stok_tidak_bisa_diubah_lewat_form_master(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['stock' => 17]);

        $this->actingAs($admin)
            ->put(route('products.update', $product), $this->payloadProduk([
                'category_id' => $product->category_id,
                'sku' => $product->sku,
                'stock' => 999, // upaya manipulasi stok lewat form
            ]))
            ->assertRedirect(route('products.index'));

        $this->assertSame(17, (int) $product->fresh()->stock);
    }

    public function test_produk_dalam_riwayat_transaksi_tidak_bisa_dihapus(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 10]);

        $this->buatPenjualan($user, [[$product, 1]]);

        $response = $this->actingAs($admin)
            ->delete(route('products.destroy', $product));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_produk_bisa_dihapus_jika_belum_pernah_ditransaksikan(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();

        $this->actingAs($admin)
            ->delete(route('products.destroy', $product))
            ->assertRedirect(route('products.index'));

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_pencarian_dan_filter_stok_bekerja(): void
    {
        $admin = User::factory()->admin()->create();
        Product::factory()->create(['name' => 'Pensil Warna Merah', 'sku' => 'PNM-01']);
        Product::factory()->lowStock()->create(['name' => 'Kertas A4', 'sku' => 'KRT-04']);

        $this->actingAs($admin)
            ->get(route('products.index', ['q' => 'Pensil']))
            ->assertOk()
            ->assertSee('Pensil Warna Merah')
            ->assertDontSee('Kertas A4');

        $this->actingAs($admin)
            ->get(route('products.index', ['q' => 'KRT-04']))
            ->assertOk()
            ->assertSee('Kertas A4');

        $this->actingAs($admin)
            ->get(route('products.low-stock'))
            ->assertOk()
            ->assertSee('Kertas A4')
            ->assertDontSee('Pensil Warna Merah');
    }

    public function test_pagination_produk(): void
    {
        $admin = User::factory()->admin()->create();
        Product::factory()->count(13)->create();

        $halaman1 = $this->actingAs($admin)->get(route('products.index', ['page' => 1]));
        $halaman2 = $this->actingAs($admin)->get(route('products.index', ['page' => 2]));

        $halaman1->assertOk();
        $halaman2->assertOk();
        $this->assertCount(12, $halaman1->viewData('products')->items());
        $this->assertCount(1, $halaman2->viewData('products')->items());
        $this->assertSame(13, $halaman1->viewData('products')->total());
    }
}
