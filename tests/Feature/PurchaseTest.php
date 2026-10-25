<?php

namespace Tests\Feature;

use App\Exceptions\InvalidTransactionException;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use App\Services\PurchaseService;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatTransaksi;
use Tests\TestCase;

class PurchaseTest extends TestCase
{
    use MembuatTransaksi, RefreshDatabase;

    private function payloadPembelian(Product $product, array $ubah = []): array
    {
        return array_merge([
            'supplier_id' => Supplier::factory()->create()->id,
            'purchased_at' => now()->format('Y-m-d'),
            'items' => [
                ['product_id' => $product->id, 'quantity' => 5],
            ],
        ], $ubah);
    }

    public function test_pembelian_menambah_stok_semua_item(): void
    {
        $user = User::factory()->create();
        $a = Product::factory()->create(['stock' => 10, 'purchase_price' => 8000]);
        $b = Product::factory()->create(['stock' => 3, 'purchase_price' => 20000]);

        $response = $this->actingAs($user)->post(route('purchases.store'), [
            'supplier_id' => Supplier::factory()->create()->id,
            'items' => [
                ['product_id' => $a->id, 'quantity' => 4],
                ['product_id' => $b->id, 'quantity' => 7],
            ],
        ]);

        $response->assertRedirect();
        $this->assertSame(14, (int) $a->fresh()->stock);
        $this->assertSame(10, (int) $b->fresh()->stock);

        $purchase = Purchase::first();
        $this->assertSame((float) (4 * 8000 + 7 * 20000), (float) $purchase->total);
        $this->assertDatabaseHas('purchase_items', [
            'product_id' => $a->id,
            'quantity' => 4,
            'unit_cost' => 8000,
            'subtotal' => 32000,
        ]);
    }

    public function test_total_dihitung_server_bukan_dari_browser(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 0, 'purchase_price' => 12500]);

        $this->actingAs($user)->post(route('purchases.store'), [
            'supplier_id' => Supplier::factory()->create()->id,
            'total' => 1, // manipulasi: browser mencoba mengirim total palsu
            'items' => [
                ['product_id' => $product->id, 'quantity' => 3, 'unit_cost' => 1],
            ],
        ])->assertRedirect();

        // Total = qty × harga beli asli dari database, bukan angka kiriman browser.
        $this->assertSame(37500.0, (float) Purchase::first()->total);
    }

    public function test_item_tidak_valid_membatalkan_seluruh_transaksi(): void
    {
        $user = User::factory()->create();
        $a = Product::factory()->create(['stock' => 5]);
        $supplier = Supplier::factory()->create();

        try {
            app(PurchaseService::class)->create($user, ['supplier_id' => $supplier->id], [
                ['product_id' => $a->id, 'quantity' => 2],
                ['product_id' => 999999, 'quantity' => 2],
            ]);
            $this->fail('Seharusnya melempar InvalidTransactionException.');
        } catch (InvalidTransactionException) {
            // diterima
        }

        // Rollback penuh: tidak ada header, tidak ada item, stok tidak berubah.
        $this->assertDatabaseCount('purchases', 0);
        $this->assertDatabaseCount('purchase_items', 0);
        $this->assertSame(5, (int) $a->fresh()->stock);
    }

    public function test_kuantitas_negatif_ditolak_validasi(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 5]);

        $this->actingAs($user)
            ->from(route('purchases.create'))
            ->post(route('purchases.store'), $this->payloadPembelian($product, [
                'items' => [['product_id' => $product->id, 'quantity' => -2]],
            ]))
            ->assertSessionHasErrors('items.0.quantity');

        $this->assertSame(5, (int) $product->fresh()->stock);
        $this->assertDatabaseCount('purchases', 0);
    }

    public function test_pembatalan_mengurangi_stok_persis_satu_kali(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 2]);
        $purchase = $this->buatPembelian($user, [[$product, 8]]);

        $this->assertSame(10, (int) $product->fresh()->stock);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('purchases.cancel', $purchase))
            ->assertSessionHas('success');

        $this->assertSame(2, (int) $product->fresh()->stock);
        $this->assertSame(Purchase::STATUS_CANCELLED, $purchase->fresh()->status);

        // Pembatalan kedua ditolak dan tidak mengubah stok sama sekali.
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('purchases.cancel', $purchase))
            ->assertSessionHas('error');

        $this->assertSame(2, (int) $product->fresh()->stock);
    }

    public function test_pembatalan_ditolak_jika_stok_sudah_dipakai_transaksi_lain(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 0]);
        $purchase = $this->buatPembelian($user, [[$product, 10]]);
        $this->assertSame(10, (int) $product->fresh()->stock);

        // Stok dipakai habis oleh penjualan lain, sehingga efek pembelian tak bisa dibalik.
        $this->buatPenjualan(User::factory()->create(), [[$product, 10]]);
        $this->assertSame(0, (int) $product->fresh()->stock);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('purchases.cancel', $purchase))
            ->assertSessionHas('error');

        // Stok tidak pernah menjadi negatif dan status tetap Selesai.
        $this->assertSame(0, (int) $product->fresh()->stock);
        $this->assertSame(Purchase::STATUS_COMPLETED, $purchase->fresh()->status);
    }

    public function test_nomor_urut_berurutan_per_tanggal(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 0]);
        $tanggal = '2026-03-10';

        $pertama = $this->buatPembelian($user, [[$product, 1]], null, $tanggal);
        $kedua = $this->buatPembelian($user, [[$product, 1]], null, $tanggal);

        $this->assertSame('PO-20260310-0001', $pertama->purchase_number);
        $this->assertSame('PO-20260310-0002', $kedua->purchase_number);
    }

    public function test_staff_dapat_mencatat_pembelian(): void
    {
        $staff = User::factory()->create();
        $product = Product::factory()->create(['stock' => 0]);

        $this->actingAs($staff)
            ->post(route('purchases.store'), $this->payloadPembelian($product))
            ->assertRedirect();

        $this->assertSame(5, (int) $product->fresh()->stock);
    }

    public function test_subtotal_dan_total_pembelian_presisi_desimal(): void
    {
        $user = User::factory()->create();
        $a = Product::factory()->create(['stock' => 0, 'purchase_price' => 3333.33]);
        $b = Product::factory()->create(['stock' => 0, 'purchase_price' => 1250.55]);

        $this->buatPembelian($user, [[$a, 3], [$b, 4]]);

        $purchase = Purchase::first();
        $itemA = $purchase->items()->where('product_id', $a->id)->first();
        $itemB = $purchase->items()->where('product_id', $b->id)->first();

        $this->assertSame('9999.99', (string) $itemA->subtotal);
        $this->assertSame('5002.20', (string) $itemB->subtotal);
        $this->assertSame('15002.19', (string) $purchase->total);
    }

    public function test_tabrakan_nomor_saat_transaksi_bersamaan_ditangani_dengan_retry(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 0]);
        $supplier = Supplier::factory()->create();

        // Transaksi pertama mengambil nomor hari ini: PO-YYYYMMDD-0001.
        $pertama = $this->buatPembelian($user, [[$product, 1]], $supplier);
        $prefix = 'PO-'.now()->format('Ymd').'-';

        // Simulasi dua proses yang menghasilkan nomor sama.
        $service = new class extends PurchaseService
        {
            public int $panggilan = 0;

            public string $nomorBertabrakan = '';

            protected function nextNumber(CarbonInterface $date): string
            {
                $this->panggilan++;

                return $this->panggilan === 1
                    ? $this->nomorBertabrakan // memicu duplicate-key → rollback
                    : parent::nextNumber($date);
            }
        };
        $service->nomorBertabrakan = $pertama->purchase_number;

        $kedua = $service->create($user, ['supplier_id' => $supplier->id], [
            ['product_id' => $product->id, 'quantity' => 2],
        ]);

        $this->assertSame(2, $service->panggilan);
        $this->assertSame($prefix.'0002', $kedua->purchase_number);
        $this->assertSame(Purchase::STATUS_COMPLETED, $pertama->fresh()->status);
        $this->assertSame(3, (int) $product->fresh()->stock);
        $this->assertSame(2, Purchase::count());
    }
}
