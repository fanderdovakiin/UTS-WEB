<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidTransactionException;
use App\Http\Requests\SaleRequest;
use App\Models\Product;
use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function __construct(private readonly SaleService $sales) {}

    public function index(Request $request)
    {
        $sales = Sale::query()
            ->with('user');
        // Admin melihat semua; staff hanya penjualan yang ia catat sendiri.
        $this->batasiTransaksiUntukRole($sales, $request->user());

        // Filter divalidasi/dinormalkan dulu: tanggal harus YYYY-MM-DD valid,
        // status harus salah satu yang sah — nilai tak valid diabaikan
        // sebelum menyentuh query (bukan menghasilkan SQL/hasil yang aneh).
        $filter = $this->normalkanFilterTanggal($request);
        $status = $this->normalkanFilterStatus($request, [
            Sale::STATUS_COMPLETED,
            Sale::STATUS_CANCELLED,
        ]);

        $sales->when($request->filled('q'), function ($query) use ($request) {
            $q = $request->string('q');
            $query->where(function ($sub) use ($q) {
                $sub->where('sale_number', 'like', "%{$q}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$q}%"));
            });
        })
            ->when($status, fn ($query, $nilai) => $query->where('status', $nilai))
            ->when($filter['dari'], fn ($query, $nilai) => $query->whereDate('sold_at', '>=', $nilai))
            ->when($filter['sampai'], fn ($query, $nilai) => $query->whereDate('sold_at', '<=', $nilai))
            ->latest('sold_at');

        $sales = $sales->paginate(10)->withQueryString();

        return view('sales.index', compact('sales'));
    }

    public function create()
    {
        return view('sales.create', [
            'products' => Product::orderBy('name')->get(),
        ]);
    }

    public function store(SaleRequest $request)
    {
        $validated = $request->validated();
        $header = [
            'note' => $validated['note'] ?? null,
            'sold_at' => $validated['sold_at'] ?? null,
        ];

        try {
            $sale = $this->sales->create(
                auth()->user(),
                $header,
                $validated['items'],
            );
        } catch (InsufficientStockException|InvalidTransactionException $e) {
            // Stok tidak cukup / item tidak sah: tidak ada data yang tersimpan (rollback).
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('sales.show', $sale)
            ->with('success', 'Penjualan '.$sale->sale_number
                .' berhasil disimpan dan stok telah dikurangi.');
    }

    public function show(Sale $sale)
    {
        // Staff hanya boleh membuka detail penjualan miliknya sendiri (anti-IDOR).
        $this->pastikanBolehMelihatTransaksi($sale->user, auth()->user());

        $sale->load(['user', 'items.product']);

        return view('sales.show', compact('sale'));
    }

    /** Pembatalan: mengembalikan barang terjual ke stok, tepat sekali, atomik. */
    public function cancel(Sale $sale)
    {
        try {
            $this->sales->cancel($sale);
        } catch (InsufficientStockException|InvalidTransactionException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Penjualan '.$sale->sale_number
            .' dibatalkan dan stok telah dikembalikan.');
    }
}
