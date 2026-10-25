<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidTransactionException;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\User;
use App\Services\Concerns\PenomoranAman;
use App\Support\Money;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    use PenomoranAman;

    /**
     * Simpan pembelian (stock-in): header + item + penambahan stok dalam satu transaksi database.
     * Harga biaya diambil dari products.purchase_price, tidak pernah dari request browser.
     *
     * @param  array{supplier_id:int|string, note?:string|null, purchased_at?:string}  $header
     * @param  list<array{product_id:int|string, quantity:int|string}>  $items
     */
    public function create(User $user, array $header, array $items): Purchase
    {
        $rows = $this->normalizeItems($items);
        $purchasedAt = isset($header['purchased_at']) && $header['purchased_at']
            ? Carbon::parse($header['purchased_at'])
            : Carbon::now();

        return $this->denganRetryNomor(function () use ($user, $header, $rows, $purchasedAt) {
            return DB::transaction(function () use ($user, $header, $rows, $purchasedAt) {
                $products = $this->lockProducts(array_column($rows, 'product_id'));

                // Semua perhitungan uang memakai bcmath (App\Support\Money) — bukan float.
                $total = '0';
                foreach ($rows as &$row) {
                    $product = $products->get($row['product_id']);

                    // Sumber kebenaran harga: database, bukan input browser.
                    $row['unit_cost'] = $product->purchase_price;
                    $row['subtotal'] = Money::kali($product->purchase_price, $row['quantity']);
                    $total = Money::tambah($total, $row['subtotal']);
                }
                unset($row);

                $purchase = Purchase::create([
                    'supplier_id' => (int) $header['supplier_id'],
                    'user_id' => $user->id,
                    'purchase_number' => $this->nextNumber($purchasedAt),
                    'total' => $total,
                    'status' => Purchase::STATUS_COMPLETED,
                    'note' => $header['note'] ?? null,
                    'purchased_at' => $purchasedAt,
                ]);

                foreach ($rows as $row) {
                    $purchase->items()->create([
                        'product_id' => $row['product_id'],
                        'quantity' => $row['quantity'],
                        'unit_cost' => $row['unit_cost'],
                        'subtotal' => $row['subtotal'],
                    ]);

                    $product = $products->get($row['product_id']);
                    $product->stock += $row['quantity'];
                    $product->save();
                }

                return $purchase->load(['supplier', 'user', 'items.product']);
            });
        });
    }

    /**
     * Batalkan pembelian: kurangi stok sebesar kuantitas yang pernah ditambahkan.
     * Bila stok tidak mencukupi untuk membalik, seluruh pembatalan dibatalkan (rollback).
     */
    public function cancel(Purchase $purchase): Purchase
    {
        return DB::transaction(function () use ($purchase) {
            // Kunci baris header supaya pembatalan dobel tidak mungkin terjadi.
            $locked = Purchase::whereKey($purchase->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== Purchase::STATUS_COMPLETED) {
                throw new InvalidTransactionException(
                    'Hanya transaksi berstatus Selesai yang dapat dibatalkan.'
                );
            }

            $items = $locked->items()->get();
            $kebutuhan = $items->groupBy('product_id')
                ->map(fn ($group) => $group->sum('quantity'));

            $products = $this->lockProducts($kebutuhan->keys()->all());

            $kurang = [];
            foreach ($kebutuhan as $productId => $qty) {
                $product = $products->get((int) $productId);

                if ($product->stock < $qty) {
                    $kurang[] = sprintf(
                        '%s (tersedia %d, perlu dikembalikan %d)',
                        $product->name,
                        $product->stock,
                        $qty
                    );
                }
            }

            if ($kurang !== []) {
                throw new InsufficientStockException(
                    'Pembatalan ditolak karena stok tidak mencukupi: '.implode('; ', $kurang)
                    .'. Stok telah dipakai transaksi lain sehingga efek pembelian ini tidak bisa dibalik.'
                );
            }

            foreach ($kebutuhan as $productId => $qty) {
                $product = $products->get((int) $productId);
                $product->stock -= $qty;
                $product->save();
            }

            $locked->status = Purchase::STATUS_CANCELLED;
            $locked->save();

            return $locked->load(['supplier', 'user', 'items.product']);
        });
    }

    /**
     * Gabungkan item yang sama produknya, buang duplikat, dan buang baris kosong.
     *
     * @param  list<array{product_id:int|string, quantity:int|string}>  $items
     * @return list<array{product_id:int, quantity:int}>
     */
    private function normalizeItems(array $items): array
    {
        $tergabung = [];

        foreach ($items as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);

            if ($productId <= 0 || $quantity <= 0) {
                continue;
            }

            $tergabung[$productId] = ($tergabung[$productId] ?? 0) + $quantity;
        }

        $rows = [];
        foreach ($tergabung as $productId => $quantity) {
            $rows[] = ['product_id' => $productId, 'quantity' => $quantity];
        }

        // Diurutkan berdasarkan ID supaya dua transaksi bersamaan mengunci baris
        // dengan urutan yang sama dan tidak saling deadlock.
        usort($rows, fn ($a, $b) => $a['product_id'] <=> $b['product_id']);

        return $rows;
    }

    /**
     * Kunci baris produk yang akan diubah stoknya.
     *
     * @param  list<int|string>  $productIds
     * @return Collection<int, Product>
     */
    private function lockProducts(array $productIds)
    {
        $ids = collect($productIds)->map(fn ($id) => (int) $id)->unique()->sort()->values();

        $products = Product::whereIn('id', $ids)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $hilang = $ids->reject(fn ($id) => $products->has($id));

        if ($hilang->isNotEmpty()) {
            throw new InvalidTransactionException(
                'Produk dengan ID '.implode(', ', $hilang->all()).' tidak ditemukan.'
            );
        }

        return $products;
    }

    /** Nomor urut per tanggal transaksi: PO-YYYYMMDD-0001. */
    protected function nextNumber(CarbonInterface $date): string
    {
        $prefix = 'PO-'.$date->format('Ymd').'-';

        $terpakai = Purchase::whereDate('purchased_at', $date->toDateString())
            ->pluck('purchase_number')
            ->all();

        $urut = 1;
        while (in_array($prefix.str_pad((string) $urut, 4, '0', STR_PAD_LEFT), $terpakai, true)) {
            $urut++;
        }

        return $prefix.str_pad((string) $urut, 4, '0', STR_PAD_LEFT);
    }
}
