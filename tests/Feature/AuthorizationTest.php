<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\Concerns\MembuatTransaksi;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use MembuatTransaksi, RefreshDatabase;

    public function test_tamu_tidak_bisa_mengakses_halaman_apapun(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('products.index'))->assertRedirect(route('login'));
        $this->get(route('purchases.index'))->assertRedirect(route('login'));
    }

    public function test_staff_tidak_bisa_mengakses_rute_master_data(): void
    {
        $staff = User::factory()->create();

        $this->actingAs($staff)->get(route('categories.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('categories.create'))->assertForbidden();
        $this->actingAs($staff)->post(route('categories.store'), ['name' => 'Baru'])->assertForbidden();
        // Index pemasok memang terbuka untuk staff (dipilih saat membuat pembelian).
        $this->actingAs($staff)->get(route('suppliers.index'))->assertOk();
        $this->actingAs($staff)->get(route('suppliers.create'))->assertForbidden();
        $this->actingAs($staff)->post(route('suppliers.store'), ['name' => 'Baru'])->assertForbidden();

        // Tidak ada data yang tersimpan walau rute ditekan langsung.
        $this->assertDatabaseMissing('categories', ['name' => 'Baru']);
        $this->assertDatabaseMissing('suppliers', ['name' => 'Baru']);
    }

    public function test_staff_tidak_bisa_mengubah_atau_menghapus_produk(): void
    {
        $staff = User::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($staff)->get(route('products.create'))->assertForbidden();

        // Form edit dibuka lewat URL langsung juga ditolak di server —
        // tidak hanya disembunyikan lewat @can di Blade.
        $this->actingAs($staff)->get(route('products.edit', $product))->assertForbidden();

        $this->actingAs($staff)
            ->put(route('products.update', $product), [
                'category_id' => $product->category_id,
                'sku' => $product->sku,
                'name' => 'Diubah Oleh Staff',
                'purchase_price' => 1000,
                'selling_price' => 2000,
                'minimum_stock' => 1,
                'unit' => 'pcs',
            ])
            ->assertForbidden();

        $this->actingAs($staff)->delete(route('products.destroy', $product))->assertForbidden();
        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => $product->name]);
    }

    public function test_staff_tidak_bisa_mengelola_pengguna(): void
    {
        $staff = User::factory()->create();
        $target = User::factory()->create();

        $this->actingAs($staff)->get(route('users.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('users.create'))->assertForbidden();
        $this->actingAs($staff)->delete(route('users.destroy', $target))->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }

    public function test_staff_tidak_bisa_membatalkan_transaksi(): void
    {
        $staff = User::factory()->create();
        $sale = $this->buatPenjualan(User::factory()->create(), [[Product::factory()->create(), 1]]);

        $this->actingAs($staff)
            ->post(route('sales.cancel', $sale))
            ->assertForbidden();

        $this->assertSame(Sale::STATUS_COMPLETED, $sale->fresh()->status);
    }

    public function test_staff_boleh_melihat_produk_dan_mencatat_transaksi(): void
    {
        $staff = User::factory()->create();
        $category = Category::factory()->create();

        $this->actingAs($staff)->get(route('products.index'))->assertOk();
        $this->actingAs($staff)->get(route('products.low-stock'))->assertOk();
        $this->actingAs($staff)->get(route('suppliers.index'))->assertOk();
        $this->actingAs($staff)->get(route('purchases.create'))->assertOk();
        $this->actingAs($staff)->get(route('sales.create'))->assertOk();
        $this->assertNotNull($category);
    }

    public function test_admin_bisa_mengakses_semua_rute_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('categories.index'))->assertOk();
        $this->actingAs($admin)->get(route('suppliers.index'))->assertOk();
        $this->actingAs($admin)->get(route('users.index'))->assertOk();
        // Form edit produk tetap terbuka untuk admin.
        $this->actingAs($admin)->get(route('products.edit', Product::factory()->create()))->assertOk();
    }

    public function test_rute_detail_kategori_dan_pengguna_tidak_didaftarkan(): void
    {
        $admin = User::factory()->admin()->create();
        $kategori = Category::factory()->create();
        $pengguna = User::factory()->create();

        // Resource dikecualikan show: tidak ada nama rute sama sekali,
        // sehingga tidak ada rute yang menunjuk ke method tak tersedia.
        $this->assertFalse(Route::has('categories.show'));
        $this->assertFalse(Route::has('users.show'));

        // URL detail GET tidak punya rute (hanya PUT/PATCH/DELETE yang cocok
        // path-nya) → 405 Method Not Allowed; method show tak pernah
        // dipanggil, jadi tidak ada rute yang menunjuk ke method tak tersedia.
        $this->actingAs($admin)->get('/kategori/'.$kategori->id)->assertStatus(405);
        $this->actingAs($admin)->get('/pengguna/'.$pengguna->id)->assertStatus(405);
    }
}
