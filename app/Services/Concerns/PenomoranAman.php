<?php

namespace App\Services\Concerns;

use Illuminate\Database\QueryException;

/**
 * Jaring pengaman penomoran transaksi saat ada percobaan bersamaan.
 *
 * Mekanisme:
 * - Kolom nomor transaksi (purchase_number / sale_number) punya UNIQUE
 *   constraint di database → nomor ganda TIDAK PERNAH tersimpan; tabrakan
 *   berarti rollback penuh tanpa data parsial.
 * - Bila dua proses sempat menghasilkan nomor sama (saling bergantian di
 *   antara pembacaan dan insert), exception duplicate-key memicu percobaan
 *   ulang dengan nomor berikutnya, maksimal 3 kali; setelah itu error
 *   diteruskan apa adanya.
 *
 * Sifat aman-konkurensi ini didukung test: PurchaseTest/SaleTest
 * "tabrakan nomor saat transaksi bersamaan ditangani dengan retry".
 */
trait PenomoranAman
{
    /**
     * @template TReturn
     *
     * @param  callable(): TReturn  $buat
     * @return TReturn
     */
    private function denganRetryNomor(callable $buat): mixed
    {
        $percobaan = 0;

        while (true) {
            try {
                return $buat();
            } catch (QueryException $e) {
                $percobaan++;

                if ($percobaan >= 3 || ! $this->adalahTabrakanNomor($e)) {
                    throw $e;
                }
            }
        }
    }

    /** Deteksi duplicate-key yang portabel: SQLite dan MySQL/MariaDB. */
    private function adalahTabrakanNomor(QueryException $e): bool
    {
        $pesan = $e->getMessage();

        return str_contains($pesan, 'UNIQUE constraint failed') // SQLite
            || str_contains($pesan, 'Duplicate entry');          // MySQL/MariaDB
    }
}
