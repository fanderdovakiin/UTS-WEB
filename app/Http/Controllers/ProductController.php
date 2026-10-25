<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::orderBy('name')->get();

        $products = Product::query()
            ->with('category')
            ->when($request->filled('q'), function ($query) use ($request) {
                $q = $request->string('q');
                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('sku', 'like', "%{$q}%");
                });
            })
            ->when($request->filled('category_id'), function ($query) use ($request) {
                $query->where('category_id', $request->integer('category_id'));
            })
            ->when($request->filled('stok'), function ($query) use ($request) {
                $request->input('stok') === 'rendah'
                    ? $query->whereColumn('stock', '<=', 'minimum_stock')
                    : $query->whereColumn('stock', '>', 'minimum_stock');
            })
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('products.index', compact('products', 'categories'));
    }

    public function create()
    {
        return view('products.form', [
            'product' => new Product,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function store(ProductRequest $request)
    {
        Product::create($request->validated());

        return redirect()->route('products.index')
            ->with('success', 'Produk berhasil ditambahkan.');
    }

    public function show(Request $request, Product $product)
    {
        $product->load('category');

        // Admin melihat seluruh riwayat; staff HANYA riwayat dari transaksi
        // yang ia catat sendiri — nomor, tanggal, jumlah, subtotal, dan tautan
        // transaksi orang lain tidak pernah ikut ter-query (bukan sekadar
        // disembunyikan di Blade).
        $pembelian = $this->batasiRiwayatItemUntukRole(
            $product->purchaseItems(),
            $request->user(),
            'purchase',
        )->with('purchase.supplier')->latest()->limit(8)->get();

        $penjualan = $this->batasiRiwayatItemUntukRole(
            $product->saleItems(),
            $request->user(),
            'sale',
        )->with('sale.user')->latest()->limit(8)->get();

        return view('products.show', compact('product', 'pembelian', 'penjualan'));
    }

    public function edit(Product $product)
    {
        return view('products.form', [
            'product' => $product,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function update(ProductRequest $request, Product $product)
    {
        $data = $request->validated();

        // Stok tidak boleh diubah dari form master: perubahan stok hanya terjadi
        // lewat pembelian (tambah) dan penjualan/pembatalan (kurang/kembali),
        // supaya riwayat transaksi tetap bisa dipertanggungjawabkan.
        unset($data['stock']);

        $product->update($data);

        return redirect()->route('products.index')
            ->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product)
    {
        $dipakaiPenjualan = $product->saleItems()->exists();
        $dipakaiPembelian = $product->purchaseItems()->exists();

        if ($dipakaiPenjualan || $dipakaiPembelian) {
            return back()->with('error', 'Produk "'.$product->name
                .'" tidak dapat dihapus karena sudah tercatat dalam riwayat transaksi.');
        }

        $product->delete();

        return redirect()->route('products.index')
            ->with('success', 'Produk berhasil dihapus.');
    }

    /** Halaman laporan stok rendah: stock <= minimum_stock. */
    public function lowStock(Request $request)
    {
        $products = Product::query()
            ->with('category')
            ->whereColumn('stock', '<=', 'minimum_stock')
            ->when($request->filled('q'), function ($query) use ($request) {
                $q = $request->string('q');
                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('sku', 'like', "%{$q}%");
                });
            })
            ->orderBy('stock')
            ->paginate(12)
            ->withQueryString();

        return view('products.low-stock', compact('products'));
    }
}
