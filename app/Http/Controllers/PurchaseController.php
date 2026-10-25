<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidTransactionException;
use App\Http\Requests\PurchaseRequest;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\PurchaseService;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    public function __construct(private readonly PurchaseService $purchases) {}

    public function index(Request $request)
    {
        $purchases = Purchase::query()
            ->with(['supplier', 'user']);
        // Admin melihat semua; staff hanya pembelian yang ia catat sendiri.
        $this->batasiTransaksiUntukRole($purchases, $request->user());

        // Filter divalidasi/dinormalkan dulu: tanggal harus YYYY-MM-DD valid,
        // status harus salah satu yang sah — nilai tak valid diabaikan
        // sebelum menyentuh query (bukan menghasilkan SQL/hasil yang aneh).
        $filter = $this->normalkanFilterTanggal($request);
        $status = $this->normalkanFilterStatus($request, [
            Purchase::STATUS_COMPLETED,
            Purchase::STATUS_CANCELLED,
        ]);

        $purchases->when($request->filled('q'), function ($query) use ($request) {
            $q = $request->string('q');
            $query->where(function ($sub) use ($q) {
                $sub->where('purchase_number', 'like', "%{$q}%")
                    ->orWhereHas('supplier', fn ($s) => $s->where('name', 'like', "%{$q}%"));
            });
        })
            ->when($status, fn ($query, $nilai) => $query->where('status', $nilai))
            ->when($filter['dari'], fn ($query, $nilai) => $query->whereDate('purchased_at', '>=', $nilai))
            ->when($filter['sampai'], fn ($query, $nilai) => $query->whereDate('purchased_at', '<=', $nilai))
            ->latest('purchased_at');

        $purchases = $purchases->paginate(10)->withQueryString();

        return view('purchases.index', compact('purchases'));
    }

    public function create()
    {
        return view('purchases.create', [
            'suppliers' => Supplier::orderBy('name')->get(),
            'products' => Product::orderBy('name')->get(['id', 'sku', 'name', 'purchase_price', 'stock', 'unit']),
        ]);
    }

    public function store(PurchaseRequest $request)
    {
        $validated = $request->validated();
        $header = [
            'supplier_id' => $validated['supplier_id'],
            'note' => $validated['note'] ?? null,
            'purchased_at' => $validated['purchased_at'] ?? null,
        ];

        try {
            $purchase = $this->purchases->create(
                auth()->user(),
                $header,
                $validated['items'],
            );
        } catch (InsufficientStockException|InvalidTransactionException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('purchases.show', $purchase)
            ->with('success', 'Pembelian '.$purchase->purchase_number
                .' berhasil disimpan dan stok telah ditambahkan.');
    }

    public function show(Purchase $purchase)
    {
        // Staff hanya boleh membuka detail pembelian miliknya sendiri (anti-IDOR).
        $this->pastikanBolehMelihatTransaksi($purchase->user, auth()->user());

        $purchase->load(['supplier', 'user', 'items.product']);

        return view('purchases.show', compact('purchase'));
    }

    /** Pembatalan: membalik penambahan stok, atomik, hanya untuk admin. */
    public function cancel(Purchase $purchase)
    {
        try {
            $this->purchases->cancel($purchase);
        } catch (InsufficientStockException|InvalidTransactionException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Pembelian '.$purchase->purchase_number
            .' dibatalkan dan stok produk telah dikembalikan.');
    }
}
