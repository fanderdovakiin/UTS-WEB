<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * Aturan visibilitas transaksi (pembelian & penjualan):
     *
     * - Admin/Owner  → melihat SEMUA transaksi (sesuai requirement dosen).
     * - Staff        → hanya transaksi yang ia catat sendiri.
     *
     * Aturan ini ditegakkan di query server (bukan lewat penyembunyian tombol
     * di Blade), sehingga mengganti ID di URL pun tetap tidak membocorkan data.
     *
     * @param  Builder<Purchase|Sale>|Relation<Purchase|Sale>  $query
     * @return Builder<Purchase|Sale>|Relation<Purchase|Sale>
     */
    protected function batasiTransaksiUntukRole(Builder|Relation $query, User $user): Builder|Relation
    {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where('user_id', $user->id);
    }

    /**
     * Aturan visibilitas riwayat ITEM transaksi (purchase_items / sale_items):
     * item hanya ikut tampil bila transaksi induknya boleh dilihat pembaca —
     * admin melihat seluruh riwayat, staff hanya dari transaksi miliknya sendiri.
     *
     * Dipakai untuk riwayat di halaman detail produk & pemasok supaya nomor,
     * tanggal, jumlah, subtotal, dan tautan transaksi orang lain tidak bocor.
     *
     * @param  Builder<Purchase|Sale>|Relation<Purchase|Sale>  $riwayat  query item transaksi
     * @param  string  $relasiInduk  relasi menuju transaksi induk ('purchase'/'sale')
     * @return Builder<Purchase|Sale>|Relation<Purchase|Sale>
     */
    protected function batasiRiwayatItemUntukRole(Builder|Relation $riwayat, User $user, string $relasiInduk): Builder|Relation
    {
        if ($user->isAdmin()) {
            return $riwayat;
        }

        return $riwayat->whereHas(
            $relasiInduk,
            fn ($query) => $query->where('user_id', $user->id)
        );
    }

    /**
     * Validasi filter tanggal daftar transaksi SEBELUM masuk query:
     * hanya nilai berformat ketat YYYY-MM-DD dengan tanggal kalender valid
     * yang dipakai; nilai tak valid diabaikan (bukan memunculkan error 500).
     *
     * @return array{dari: ?string, sampai: ?string}
     */
    protected function normalkanFilterTanggal(Request $request): array
    {
        $hasil = ['dari' => null, 'sampai' => null];

        foreach (['dari', 'sampai'] as $kunci) {
            if (! $request->filled($kunci)) {
                continue;
            }

            $nilai = $request->input($kunci);

            if (is_string($nilai)
                && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $nilai, $bagian)
                && checkdate((int) $bagian[2], (int) $bagian[3], (int) $bagian[1])) {
                $hasil[$kunci] = $nilai;
            }
        }

        return $hasil;
    }

    /**
     * Validasi filter status daftar transaksi SEBELUM masuk query:
     * hanya status yang sah (daftar $izinkan) yang dipakai; nilai lain diabaikan.
     *
     * @param  list<string>  $izinkan
     */
    protected function normalkanFilterStatus(Request $request, array $izinkan): ?string
    {
        $nilai = $request->input('status');

        return is_string($nilai) && in_array($nilai, $izinkan, true) ? $nilai : null;
    }

    /** Guard halaman detail transaksi: staff hanya boleh membuka miliknya sendiri. */
    protected function pastikanBolehMelihatTransaksi(User $pemilik, User $pembaca): void
    {
        if ($pembaca->isAdmin()) {
            return;
        }

        abort_unless($pemilik->is($pembaca), 403, 'Anda hanya dapat melihat transaksi yang Anda catat sendiri.');
    }
}
