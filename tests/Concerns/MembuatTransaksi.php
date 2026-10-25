<?php

namespace Tests\Concerns;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;
use App\Services\PurchaseService;
use App\Services\SaleService;

/**
 * Pembuat transaksi uji coba. Selalu lewat service asli (bukan insert langsung)
 * supaya stok, nomor urut, dan total diperlakukan sama seperti lewat HTTP.
 */
trait MembuatTransaksi
{
    /** @param  array<int, array{0: Product, 1: int}>  $pasangan */
    protected function buatPembelian(User $user, array $pasangan, ?Supplier $supplier = null, ?string $tanggal = null): Purchase
    {
        $supplier ??= Supplier::factory()->create();

        return app(PurchaseService::class)->create(
            $user,
            [
                'supplier_id' => $supplier->id,
                'purchased_at' => $tanggal,
            ],
            collect($pasangan)->map(fn (array $p) => [
                'product_id' => $p[0]->id,
                'quantity' => $p[1],
            ])->all(),
        );
    }

    /** @param  array<int, array{0: Product, 1: int}>  $pasangan */
    protected function buatPenjualan(User $user, array $pasangan, ?string $tanggal = null): Sale
    {
        return app(SaleService::class)->create(
            $user,
            ['sold_at' => $tanggal],
            collect($pasangan)->map(fn (array $p) => [
                'product_id' => $p[0]->id,
                'quantity' => $p[1],
            ])->all(),
        );
    }
}
