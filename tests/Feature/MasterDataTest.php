<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatTransaksi;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    use MembuatTransaksi, RefreshDatabase;

    public function test_kategori_crud_lengkap(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('categories.store'), [
            'name' => 'Elektronik',
            'description' => 'Perangkat elektronik',
        ])->assertRedirect(route('categories.index'));

        $kategori = Category::where('name', 'Elektronik')->firstOrFail();

        $this->actingAs($admin)->put(route('categories.update', $kategori), [
            'name' => 'Elektronik & Gadget',
            'description' => 'Diperbarui',
        ])->assertRedirect(route('categories.index'));

        $this->assertDatabaseHas('categories', [
            'id' => $kategori->id,
            'name' => 'Elektronik & Gadget',
        ]);

        $this->actingAs($admin)->delete(route('categories.destroy', $kategori))
            ->assertRedirect(route('categories.index'));

        $this->assertDatabaseMissing('categories', ['id' => $kategori->id]);
    }

    public function test_kategori_punya_produk_tidak_bisa_dihapus(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();

        $this->actingAs($admin)
            ->delete(route('categories.destroy', $product->category_id))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('categories', ['id' => $product->category_id]);
    }

    public function test_admin_dapat_menambah_akun_staff_baru_yang_bisa_login(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Staff Baru',
            'email' => 'staff.baru@ruangstok.test',
            'role' => 'staff',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'staff.baru@ruangstok.test',
            'role' => 'staff',
        ]);

        // Akun baru benar-benar bisa dipakai login.
        $this->post(route('login.attempt'), [
            'email' => 'staff.baru@ruangstok.test',
            'password' => 'rahasia123',
        ])->assertRedirect(route('dashboard'));
    }

    public function test_admin_tidak_bisa_menghapus_diri_sendiri(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->delete(route('users.destroy', $admin))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_admin_terakhir_tidak_bisa_diturunkan_atau_dihapus(): void
    {
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->create();

        $this->actingAs($admin)->delete(route('users.destroy', $admin))
            ->assertSessionHas('error');

        // Menurunkan role admin terakhir juga ditolak.
        $this->actingAs($admin)->put(route('users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => 'staff',
        ])->assertSessionHas('error');

        $this->assertSame(User::ROLE_ADMIN, $admin->fresh()->role);
        $this->assertNotNull($staff);
    }

    public function test_filter_status_dan_pencarian_transaksi(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['stock' => 0]);
        $purchase = $this->buatPembelian($admin, [[$product, 10]]);

        $this->actingAs($admin)->get(route('purchases.index', ['q' => $purchase->purchase_number]))
            ->assertOk()
            ->assertSee($purchase->purchase_number);

        $this->actingAs($admin)->get(route('purchases.index', ['status' => 'completed']))
            ->assertOk()
            ->assertSee($purchase->purchase_number);

        // Batalkan, lalu pastikan filter status menemukan transaksi batal.
        $this->actingAs($admin)->post(route('purchases.cancel', $purchase));

        $this->actingAs($admin)->get(route('purchases.index', ['status' => 'cancelled']))
            ->assertOk()
            ->assertSee($purchase->purchase_number);

        $this->actingAs($admin)->get(route('purchases.index', ['status' => 'completed']))
            ->assertOk()
            ->assertDontSee('>'.$purchase->purchase_number.'<', false);
    }

    public function test_filter_rentang_tanggal_penjualan(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['stock' => 100]);

        $maret = $this->buatPenjualan($admin, [[$product, 1]], '2026-03-15');
        $this->buatPenjualan($admin, [[$product, 1]], '2026-04-15');

        $response = $this->actingAs($admin)->get(route('sales.index', [
            'dari' => '2026-03-01',
            'sampai' => '2026-03-31',
        ]));

        $response->assertOk();
        $this->assertSame($maret->id, $response->viewData('sales')->items()[0]->id);
        $this->assertCount(1, $response->viewData('sales')->items());
    }

    public function test_filter_tanggal_dan_status_tidak_valid_diabaikan(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['stock' => 50]);
        $purchase = $this->buatPembelian($admin, [[$product, 2]]);
        $sale = $this->buatPenjualan($admin, [[$product, 1]]);

        // Tanggal tak berformat/ tidak mungkin & status di luar daftar sah
        // tidak membuat error 500 dan tidak menyaring apa pun: nilai sampah
        // diabaikan sebelum masuk query.
        $this->actingAs($admin)->get(route('purchases.index', [
            'dari' => 'bukan-tanggal',
            'sampai' => '31-13-2026',
            'status' => 'status-ngawur',
        ]))->assertOk()->assertSee($purchase->purchase_number);

        $this->actingAs($admin)->get(route('sales.index', [
            'dari' => '2026-99-99',
            'sampai' => 'abc',
            'status' => "completed' OR 1=1 --",
        ]))->assertOk()->assertSee($sale->sale_number);

        // Filter sah tetap berfungsi seperti biasa.
        $this->actingAs($admin)->get(route('sales.index', ['status' => 'completed']))
            ->assertOk()
            ->assertSee($sale->sale_number);

        $this->actingAs($admin)->get(route('purchases.index', ['dari' => now()->format('Y-m-d')]))
            ->assertOk()
            ->assertSee($purchase->purchase_number);
    }
}
