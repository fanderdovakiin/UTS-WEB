<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidTransactionException;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Services\Concerns\PenomoranAman;
use App\Support\Money;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SaleService
{
    use PenomoranAman;

    /**
     * Simpan penjualan (stock-out): cek stok semua item lebih dulu, lalu header + item +
     * pengurangan stok dalam satu transaksi database. Satu item gagal = seluruh transaksi batal.
     *
     * @param  array{note?:string|null, sold_at?:string}  $header
     * @param  list<array{product_id:int|string, quantity:int|string}>  $items
     */
    public function create(User $user, array $header, array $items): Sale
    {
        $rows = $this->normalizeItems($items);
        $soldAt = isset($header['sold_at']) && $header['sold_at']
            ? Carbon::parse($header['sold_at'])
            : Carbon::now();

        return $this->denganRetryNomor(function () use ($user, $header, $rows, $soldAt) {
            return DB::transaction(function () use ($user, $header, $rows, $soldAt) {
                if ($rows === []) {
                    throw new InvalidTransactionException('Transaksi minimal harus memiliki satu item produk.');
                }

                $products = $this->lockProducts(array_column($rows, 'product_id'));

                // Cek kecukupan stok SELURUH item sebelum ada satu pun perubahan.
                $kurang = [];
                foreach ($rows as $row) {
                    $product = $products->get($row['product_id']);

                    if ($product->stock < $row['quantity']) {
                        $kurang[] = sprintf(
                            '%s (tersedia %d, diminta %d)',
                            $product->name,
                            $product->stock,
                            $row['quantity']
                        );
                    }
                }

                if ($kurang !== []) {
                    throw new InsufficientStockException(
                        'Penjualan ditolak karena stok tidak mencukupi: '.implode('; ', $kurang).'.'
                    );
                }

                // Semua perhitungan uang memakai bcmath (App\Support\Money) — bukan float.
                $total = '0';
                foreach ($rows as &$row) {
                    $product = $products->get($row['product_id']);

                    $unitPrice = $product->selling_price; // Harga jual dari database.
                    $subtotal = Money::kali($unitPrice, $row['quantity']);
                    $total = Money::tambah($total, $subtotal);

                    $row['unit_price'] = $unitPrice;
                    $row['subtotal'] = $subtotal;
                    $row['product_ref'] = $product;
                }
                unset($row);

                $sale = Sale::create([
                    'user_id' => $user->id,
                    'sale_number' => $this->nextNumber($soldAt),
                    'total' => $total,
                    'status' => Sale::STATUS_COMPLETED,
                    'note' => $header['note'] ?? null,
                    'sold_at' => $soldAt,
                ]);

                foreach ($rows as $row) {
                    $sale->items()->create([
                        'product_id' => $row['product_id'],
                        'quantity' => $row['quantity'],
                        'unit_price' => $row['unit_price'],
                        'subtotal' => $row['subtotal'],
                    ]);

                    $product = $row['product_ref'];
                    $product->stock -= $row['quantity'];
                    $product->save();
                }

                return $sale->load(['user', 'items.product']);
            });
        });
    }

    /**
     * Batalkan penjualan: kembalikan barang terjual ke stok, tepat sekali, atomik.
     */
    public function cancel(Sale $sale): Sale
    {
        return DB::transaction(function () use ($sale) {
            $locked = Sale::whereKey($sale->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== Sale::STATUS_COMPLETED) {
                throw new InvalidTransactionException(
                    'Hanya transaksi berstatus Selesai yang dapat dibatalkan.'
                );
            }

            $kebutuhan = $locked->items()->get()
                ->groupBy('product_id')
                ->map(fn ($group) => $group->sum('quantity'));

            $products = $this->lockProducts($kebutuhan->keys()->all());

            foreach ($kebutuhan as $productId => $qty) {
                $product = $products->get((int) $productId);
                $product->stock += $qty;
                $product->save();
            }

            $locked->status = Sale::STATUS_CANCELLED;
            $locked->save();

            return $locked->load(['user', 'items.product']);
        });
    }

    /**
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

        // Urutan ID konsisten untuk menghindari deadlock antar transaksi.
        usort($rows, fn ($a, $b) => $a['product_id'] <=> $b['product_id']);

        return $rows;
    }

    /**
     * @param  list<int|string>  $productIds
     * @return Collection<int, Product>
     */
    private function lockProducts(array $productIds)
    {
        $ids = collect($productIds)->map(fn ($id) => (int) $id)->unique()->sort()->values();

        if ($ids->isEmpty()) {
            return collect();
        }

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

    /** Nomor urut per tanggal transaksi: INV-YYYYMMDD-0001. */
    protected function nextNumber(CarbonInterface $date): string
    {
        $prefix = 'INV-'.$date->format('Ymd').'-';

        $terpakai = Sale::whereDate('sold_at', $date->toDateString())
            ->pluck('sale_number')
            ->all();

        $urut = 1;
        while (in_array($prefix.str_pad((string) $urut, 4, '0', STR_PAD_LEFT), $terpakai, true)) {
            $urut++;
        }

        return $prefix.str_pad((string) $urut, 4, '0', STR_PAD_LEFT);
    }
}
