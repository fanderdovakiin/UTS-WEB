<?php

namespace Tests\Feature;

use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Services\SaleService;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatTransaksi;
use Tests\TestCase;

class SaleTest extends TestCase
{
    use MembuatTransaksi, RefreshDatabase;

    public function test_penjualan_mengurangi_stok_semua_item(): void
    {
        $user = User::factory()->create();
        $a = Product::factory()->create(['stock' => 20, 'selling_price' => 15000]);
        $b = Product::factory()->create(['stock' => 8, 'selling_price' => 50000]);

        $response = $this->actingAs($user)->post(route('sales.store'), [
            'items' => [
                ['product_id' => $a->id, 'quantity' => 3],
                ['product_id' => $b->id, 'quantity' => 2],
            ],
        ]);

        $response->assertRedirect();
        $this->assertSame(17, (int) $a->fresh()->stock);
        $this->assertSame(6, (int) $b->fresh()->stock);

        $sale = Sale::first();
        $this->assertSame((float) (3 * 15000 + 2 * 50000), (float) $sale->total);
        $this->assertDatabaseHas('sale_items', [
            'product_id' => $a->id,
            'quantity' => 3,
            'unit_price' => 15000,
            'subtotal' => 45000,
        ]);
    }

    public function test_harga_jual_dihitung_server_bukan_dari_browser(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 10, 'selling_price' => 25000]);

        $this->actingAs($user)->post(route('sales.store'), [
            'total' => 100, // manipulasi
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 100],
            ],
        ])->assertRedirect();

        $this->assertSame(50000.0, (float) Sale::first()->total);
        $this->assertSame(8, (int) $product->fresh()->stock);
    }

    public function test_oversell_ditolak_tanpa_data_sebagian(): void
    {
        $user = User::factory()->create();
        $cukup = Product::factory()->create(['stock' => 50, 'name' => 'Produk Cukup']);
        $kurang = Product::factory()->create(['stock' => 2, 'name' => 'Produk Kurang']);

        $response = $this->actingAs($user)
            ->from(route('sales.create'))
            ->post(route('sales.store'), [
                'items' => [
                    ['product_id' => $cukup->id, 'quantity' => 10],
                    ['product_id' => $kurang->id, 'quantity' => 5], // stok hanya 2
                ],
            ]);

        $response->assertRedirect(route('sales.create'));
        $response->assertSessionHas('error');
        $this->assertStringContainsString('stok tidak mencukupi', session('error'));

        // Tidak ada satu pun data tersimpan: transaksi, item, maupun pengurangan stok.
        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('sale_items', 0);
        $this->assertSame(50, (int) $cukup->fresh()->stock);
        $this->assertSame(2, (int) $kurang->fresh()->stock);
    }

    public function test_dua_baris_produk_sama_digabung_sebelum_dicek_stok(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 5]);

        try {
            app(SaleService::class)->create($user, [], [
                ['product_id' => $product->id, 'quantity' => 3],
                ['product_id' => $product->id, 'quantity' => 3], // gabungan = 6 > stok 5
            ]);
            $this->fail('Seharusnya melempar InsufficientStockException.');
        } catch (InsufficientStockException $e) {
            $this->assertStringContainsString('stok tidak mencukupi', $e->getMessage());
        }

        $this->assertDatabaseCount('sales', 0);
        $this->assertSame(5, (int) $product->fresh()->stock);
    }

    public function test_pembatalan_mengembalikan_stok_tepat_satu_kali(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 10]);
        $sale = $this->buatPenjualan($user, [[$product, 4]]);

        $this->assertSame(6, (int) $product->fresh()->stock);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('sales.cancel', $sale))
            ->assertSessionHas('success');

        $this->assertSame(10, (int) $product->fresh()->stock);
        $this->assertSame(Sale::STATUS_CANCELLED, $sale->fresh()->status);

        // Pembatalan kedua ditolak: stok tidak ditambah dua kali.
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('sales.cancel', $sale))
            ->assertSessionHas('error');

        $this->assertSame(10, (int) $product->fresh()->stock);
    }

    public function test_nomor_urut_berurutan_per_tanggal(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 100]);

        $pertama = $this->buatPenjualan($user, [[$product, 1]], '2026-03-10');
        $kedua = $this->buatPenjualan($user, [[$product, 1]], '2026-03-10');

        $this->assertSame('INV-20260310-0001', $pertama->sale_number);
        $this->assertSame('INV-20260310-0002', $kedua->sale_number);
    }

    public function test_staff_dapat_mencatat_penjualan(): void
    {
        $staff = User::factory()->create();
        $product = Product::factory()->create(['stock' => 9, 'selling_price' => 10000]);

        $this->actingAs($staff)
            ->post(route('sales.store'), [
                'items' => [['product_id' => $product->id, 'quantity' => 1]],
            ])
            ->assertRedirect();

        $this->assertSame(8, (int) $product->fresh()->stock);
    }

    public function test_subtotal_dan_total_disimpan_dengan_presisi_desimal(): void
    {
        $user = User::factory()->create();
        // Harga pecahan yang memicu error floating-point bila dikalikan float biasa.
        $a = Product::factory()->create(['stock' => 50, 'selling_price' => 3333.33]);
        $b = Product::factory()->create(['stock' => 150, 'selling_price' => 1.15]);

        $this->buatPenjualan($user, [[$a, 3], [$b, 100]]);

        $sale = Sale::first();
        $itemA = $sale->items()->where('product_id', $a->id)->first();
        $itemB = $sale->items()->where('product_id', $b->id)->first();

        // Persis "9999.99" dan "115.00" — bukan 9999.9899… / 114.9999…
        $this->assertSame('9999.99', (string) $itemA->subtotal);
        $this->assertSame('115.00', (string) $itemB->subtotal);
        $this->assertSame('10114.99', (string) $sale->total);
    }

    public function test_tabrakan_nomor_saat_transaksi_bersamaan_ditangani_dengan_retry(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 100]);

        // Transaksi pertama mengambil nomor hari ini: INV-YYYYMMDD-0001.
        $pertama = $this->buatPenjualan($user, [[$product, 1]]);
        $prefix = 'INV-'.now()->format('Ymd').'-';

        // Simulasi dua proses yang menghasilkan nomor sama: service ini dipaksa
        // memakai nomor yang sudah tersimpan pada percobaan pertamanya.
        $service = new class extends SaleService
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
        $service->nomorBertabrakan = $pertama->sale_number;

        $kedua = $service->create($user, [], [
            ['product_id' => $product->id, 'quantity' => 2],
        ]);

        // Mekanisme retry bekerja: nomor berikutnya dipakai, transaksi pertama
        // tidak rusak, stok berkurang tepat dua kali (tanpa data parsial).
        $this->assertSame(2, $service->panggilan);
        $this->assertSame($prefix.'0002', $kedua->sale_number);
        $this->assertSame(Sale::STATUS_COMPLETED, $pertama->fresh()->status);
        $this->assertSame(97, (int) $product->fresh()->stock);
        $this->assertSame(2, Sale::count());
    }
}
