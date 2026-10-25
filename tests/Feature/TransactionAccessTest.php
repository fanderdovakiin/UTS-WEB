<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatTransaksi;
use Tests\TestCase;

/**
 * Aturan visibilitas transaksi:
 * - Admin/Owner → semua transaksi.
 * - Staff       → hanya transaksi yang ia catat sendiri.
 * Ditegakkan di query server + guard halaman detail (bukan penyembunyian tombol).
 */
class TransactionAccessTest extends TestCase
{
    use MembuatTransaksi, RefreshDatabase;

    public function test_staff_tidak_bisa_membuka_detail_penjualan_orang_lain_via_url(): void
    {
        $pemilik = User::factory()->create();
        $penyusup = User::factory()->create();
        $product = Product::factory()->create(['stock' => 50]);

        $penjualan = $this->buatPenjualan($pemilik, [[$product, 1]]);

        // Mengganti ID di URL ditolak meski transaksi valid dan status selesai.
        $this->actingAs($penyusup)
            ->get(route('sales.show', $penjualan))
            ->assertForbidden();

        // Pemiliknya sendiri tetap boleh membuka.
        $this->actingAs($pemilik)
            ->get(route('sales.show', $penjualan))
            ->assertOk();
    }

    public function test_staff_tidak_bisa_membuka_detail_pembelian_orang_lain_via_url(): void
    {
        $admin = User::factory()->admin()->create();
        $penyusup = User::factory()->create();
        $product = Product::factory()->create(['stock' => 0]);

        $pembelian = $this->buatPembelian($admin, [[$product, 5]]);

        $this->actingAs($penyusup)
            ->get(route('purchases.show', $pembelian))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('purchases.show', $pembelian))
            ->assertOk();
    }

    public function test_daftar_penjualan_staff_hanya_berisi_miliknya_sendiri(): void
    {
        $lain = User::factory()->create(['name' => 'Pemilik Lain']);
        $staff = User::factory()->create();
        $product = Product::factory()->create(['stock' => 100]);

        $milikLain = $this->buatPenjualan($lain, [[$product, 1]]);
        $milikSendiri = $this->buatPenjualan($staff, [[$product, 1]]);

        $response = $this->actingAs($staff)->get(route('sales.index'));

        $response->assertOk();
        $this->assertSame([$milikSendiri->id], collect($response->viewData('sales')->items())
            ->pluck('id')->all());
        $response->assertSee($milikSendiri->sale_number);
        $response->assertDontSee($milikLain->sale_number);
    }

    public function test_daftar_pembelian_staff_hanya_berisi_miliknya_sendiri(): void
    {
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->create();
        $product = Product::factory()->create(['stock' => 0]);

        $milikAdmin = $this->buatPembelian($admin, [[$product, 2]]);
        $milikStaff = $this->buatPembelian($staff, [[$product, 2]]);

        $response = $this->actingAs($staff)->get(route('purchases.index'));

        $response->assertOk();
        $this->assertSame([$milikStaff->id], collect($response->viewData('purchases')->items())
            ->pluck('id')->all());
        $response->assertDontSee($milikAdmin->purchase_number);
    }

    public function test_pencarian_staff_tidak_bisa_menembus_ke_transaksi_lain(): void
    {
        $lain = User::factory()->create();
        $staff = User::factory()->create();
        $product = Product::factory()->create(['stock' => 100]);

        $milikLain = $this->buatPenjualan($lain, [[$product, 1]]);
        $this->buatPenjualan($staff, [[$product, 1]]);

        // Staff mencari persis nomor penjualan milik orang lain: tidak ditemukan.
        $response = $this->actingAs($staff)
            ->get(route('sales.index', ['q' => $milikLain->sale_number]));

        $response->assertOk();
        $this->assertCount(0, $response->viewData('sales')->items());
        // Kata kunci memang tampil kembali di input pencarian (perilaku benar),
        // tetapi nomor itu tidak boleh muncul sebagai baris data di dalam tabel.
        $this->assertDoesNotMatchRegularExpression(
            '#<tbody>[\s\S]*'.preg_quote($milikLain->sale_number, '#').'[\s\S]*</tbody>#',
            $response->getContent(),
            'Nomor penjualan milik orang lain tidak boleh muncul di tabel hasil.'
        );
    }

    public function test_admin_melihat_seluruh_transaksi(): void
    {
        $admin = User::factory()->admin()->create();
        $lain = User::factory()->create();
        $product = Product::factory()->create(['stock' => 100]);

        $milikLain = $this->buatPenjualan($lain, [[$product, 1]]);
        $this->buatPenjualan($admin, [[$product, 1]]);

        $response = $this->actingAs($admin)->get(route('sales.index'));

        $response->assertOk();
        $this->assertCount(2, $response->viewData('sales')->items());
        $response->assertSee($milikLain->sale_number);

        $this->actingAs($admin)->get(route('sales.show', $milikLain))->assertOk();
    }

    public function test_dashboard_staff_hanya_menghitung_transaksi_miliknya(): void
    {
        $lain = User::factory()->create();
        $staff = User::factory()->create();
        $product = Product::factory()->create(['stock' => 100, 'selling_price' => 10000]);

        $milikLain = $this->buatPenjualan($lain, [[$product, 5]]);
        $this->buatPenjualan($staff, [[$product, 2]]);

        $response = $this->actingAs($staff)->get(route('dashboard'));

        $response->assertOk();
        $ringkasan = $response->viewData('ringkasan');

        // Hanya penjualan miliknya sendiri (2 × Rp 10.000) yang dihitung.
        $this->assertSame(1, $ringkasan['penjualan']);
        $this->assertSame(20000.0, $ringkasan['nilai_penjualan_bulan']);
        $this->assertSame(1, $ringkasan['penjualan_hari_ini']);
        $response->assertDontSee($milikLain->sale_number);

        // Admin tetap melihat angka global.
        $adminResponse = $this->actingAs(User::factory()->admin()->create())
            ->get(route('dashboard'));
        $this->assertSame(2, $adminResponse->viewData('ringkasan')['penjualan']);
        $adminResponse->assertSee($milikLain->sale_number);
    }

    public function test_riwayat_produk_tidak_membocorkan_transaksi_orang_lain(): void
    {
        $lain = User::factory()->create();
        $staff = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['stock' => 100]);

        $pembelianLain = $this->buatPembelian($lain, [[$product, 5]]);
        $penjualanLain = $this->buatPenjualan($lain, [[$product, 1]]);
        $penjualanStaff = $this->buatPenjualan($staff, [[$product, 1]]);

        // Staff: riwayat produk hanya berisi transaksi miliknya sendiri —
        // nomor, tanggal, jumlah, subtotal, dan tautan milik orang lain
        // tidak ikut ter-query.
        $respons = $this->actingAs($staff)->get(route('products.show', $product));
        $respons->assertOk();
        $respons->assertSee($penjualanStaff->sale_number);
        $respons->assertDontSee($penjualanLain->sale_number);
        $respons->assertDontSee($pembelianLain->purchase_number);

        // Admin: seluruh riwayat tampil (requirement dosen).
        $adminResp = $this->actingAs($admin)->get(route('products.show', $product));
        $adminResp->assertOk();
        $adminResp->assertSee($penjualanStaff->sale_number);
        $adminResp->assertSee($penjualanLain->sale_number);
        $adminResp->assertSee($pembelianLain->purchase_number);
    }

    public function test_riwayat_pemasok_tidak_membocorkan_pembelian_orang_lain(): void
    {
        $lain = User::factory()->create();
        $staff = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create(['stock' => 100]);

        $pembelianLain = $this->buatPembelian($lain, [[$product, 2]], $supplier);
        $pembelianStaff = $this->buatPembelian($staff, [[$product, 3]], $supplier);

        // Staff: halaman pemasok hanya menampilkan pembelian miliknya.
        $respons = $this->actingAs($staff)->get(route('suppliers.show', $supplier));
        $respons->assertOk();
        $respons->assertSee($pembelianStaff->purchase_number);
        $respons->assertDontSee($pembelianLain->purchase_number);

        // Admin: semua pembelian pemasok tampil.
        $adminResp = $this->actingAs($admin)->get(route('suppliers.show', $supplier));
        $adminResp->assertOk();
        $adminResp->assertSee($pembelianLain->purchase_number);
        $adminResp->assertSee($pembelianStaff->purchase_number);
    }
}
