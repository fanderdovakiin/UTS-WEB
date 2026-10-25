<?php

namespace App\Support;

/**
 * Helper perhitungan uang presisi-desimal.
 *
 * Semua operasi rupiah di RuangStok memakai bcmath (scale 2) sehingga
 * perhitungan harga × kuantitas dan penjumlahan total TIDAK bergantung pada
 * floating-point IEEE 754 (mis. 3333.33 × 3 = 9999.9899999… tanpa presisi desimal).
 *
 * Nilai keluaran berupa string desimal "12500.00" — format yang sama dengan
 * kolom decimal(15,2) di database, sehingga aman disimpan langsung oleh Eloquent.
 */
class Money
{
    /** Kalikan harga (desimal) dengan kuantitas (bulat) → string "9999.99". */
    public static function kali(string|int|float $harga, int $jumlah): string
    {
        return bcmul(self::normal($harga), (string) $jumlah, 2);
    }

    /** Jumlahkan dua nilai uang → string "12500.00". */
    public static function tambah(string|int|float $a, string|int|float $b): string
    {
        return bcadd(self::normal($a), self::normal($b), 2);
    }

    /**
     * Normalkan nilai apa pun menjadi string desimal scale-2 yang valid bcmath.
     * Menangani input model (string "12500.00" dari cast decimal) maupun int/float.
     */
    private static function normal(string|int|float $nilai): string
    {
        if (is_float($nilai)) {
            // sprintf menghindari notasi ilmiah pada angka besar.
            $nilai = sprintf('%.2F', $nilai);
        }

        // bcadd(x, '0', 2) menormalkan "12500" → "12500.00" dan membuang digit liar.
        return bcadd((string) $nilai, '0', 2);
    }
}
