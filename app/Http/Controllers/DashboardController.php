<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $hariIni = Carbon::today();
        $awalBulan = Carbon::now()->startOfMonth();
        $kini = Carbon::now();

        // Ringkasan produk/master bersifat global (tidak sensitif, dibutuhkan semua
        // role untuk operasional), sedangkan angka transaksi mengikuti aturan
        // visibilitas: admin = semua transaksi, staff = miliknya sendiri.
        $penjualanScope = fn () => $this->batasiTransaksiUntukRole(
            Sale::query()->where('status', Sale::STATUS_COMPLETED),
            $user
        );
        $pembelianScope = fn () => $this->batasiTransaksiUntukRole(
            Purchase::query()->where('status', Purchase::STATUS_COMPLETED),
            $user
        );

        // Semua angka berasal dari query agregat database, bukan hard-coded.
        $ringkasan = [
            'produk' => Product::count(),
            'stok_rendah' => Product::whereColumn('stock', '<=', 'minimum_stock')->count(),
            'supplier' => Supplier::count(),
            'pengguna' => User::count(),
            'penjualan_hari_ini' => $penjualanScope()->whereDate('sold_at', $hariIni)->count(),
            'nilai_penjualan_bulan' => (float) $penjualanScope()
                ->whereBetween('sold_at', [$awalBulan, $kini])->sum('total'),
            'nilai_pembelian_bulan' => (float) $pembelianScope()
                ->whereBetween('purchased_at', [$awalBulan, $kini])->sum('total'),
            'pembelian' => $this->batasiTransaksiUntukRole(Purchase::query(), $user)->count(),
            'penjualan' => $this->batasiTransaksiUntukRole(Sale::query(), $user)->count(),
        ];

        $terbaru = $this->transaksiTerbaru($user);

        $stokRendah = Product::with('category')
            ->whereColumn('stock', '<=', 'minimum_stock')
            ->orderBy('stock')
            ->limit(8)
            ->get();

        return view('dashboard', compact('ringkasan', 'terbaru', 'stokRendah'));
    }

    /** Gabungkan beberapa transaksi terakhir dari dua tabel (jumlah barisnya kecil). */
    private function transaksiTerbaru($user)
    {
        $penjualan = $this->batasiTransaksiUntukRole(Sale::query(), $user)
            ->with('user')->latest('sold_at')->limit(6)->get()->map(fn ($sale) => [
                'nomor' => $sale->sale_number,
                'jenis' => 'Penjualan',
                'pihak' => $sale->user->name,
                'waktu' => $sale->sold_at,
                'total' => (float) $sale->total,
                'status' => $sale->status,
                'url' => route('sales.show', $sale),
            ]);

        $pembelian = $this->batasiTransaksiUntukRole(Purchase::query(), $user)
            ->with('supplier')->latest('purchased_at')->limit(6)->get()->map(fn ($purchase) => [
                'nomor' => $purchase->purchase_number,
                'jenis' => 'Pembelian',
                'pihak' => $purchase->supplier->name,
                'waktu' => $purchase->purchased_at,
                'total' => (float) $purchase->total,
                'status' => $purchase->status,
                'url' => route('purchases.show', $purchase),
            ]);

        return $penjualan->concat($pembelian)
            ->sortByDesc(fn ($row) => $row['waktu']->timestamp)
            ->take(8)
            ->values();
    }
}
