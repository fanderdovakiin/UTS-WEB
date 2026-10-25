<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| Semua halaman operasional berada di belakang middleware auth. Hak akses
| peran (admin/staff) ditegakkan lewat Gate pada middleware can:.
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1')
        ->name('login.attempt');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/', fn () => redirect()->route('dashboard'))->name('home');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // --- Produk: dibaca semua role (dipakai saat transaksi), ditulis oleh admin.
    Route::get('/produk/tambah', [ProductController::class, 'create'])
        ->middleware('can:manage-master')
        ->name('products.create');

    Route::resource('produk', ProductController::class)
        ->parameters(['produk' => 'product'])
        ->names('products')
        ->except(['create'])
        // Form edit juga dilindungi di server: staff yang mengetik URL
        // /produk/{id}/edit langsung tetap ditolak (403), bukan hanya tombolnya.
        ->middlewareFor(['edit', 'store', 'update', 'destroy'], 'can:manage-master');

    Route::get('/stok-rendah', [ProductController::class, 'lowStock'])->name('products.low-stock');

    // --- Master data: khusus admin/owner.
    Route::middleware('can:manage-master')->group(function () {
        Route::resource('kategori', CategoryController::class)
            ->parameters(['kategori' => 'category'])
            ->names('categories')
            // Tidak ada halaman detail kategori: kecualikan show agar tidak ada
            // rute yang menunjuk ke method controller yang tidak tersedia.
            ->except(['show']);

        Route::resource('pemasok', SupplierController::class)
            ->parameters(['pemasok' => 'supplier'])
            ->names('suppliers')
            ->except(['index', 'show']);
    });

    // Pemasok tetap bisa dilihat staff untuk form pembelian.
    Route::get('/pemasok', [SupplierController::class, 'index'])->name('suppliers.index');
    Route::get('/pemasok/{supplier}', [SupplierController::class, 'show'])->name('suppliers.show');

    // --- Pengguna/role: khusus admin.
    Route::middleware('can:manage-users')->group(function () {
        Route::resource('pengguna', UserController::class)
            ->parameters(['pengguna' => 'user'])
            ->names('users')
            // Tidak ada halaman detail pengguna: kecualikan show agar tidak ada
            // rute yang menunjuk ke method controller yang tidak tersedia.
            ->except(['show']);
    });

    // --- Pembelian (stock-in).
    Route::get('/pembelian', [PurchaseController::class, 'index'])->name('purchases.index');
    Route::get('/pembelian/tambah', [PurchaseController::class, 'create'])->name('purchases.create');
    Route::post('/pembelian', [PurchaseController::class, 'store'])->name('purchases.store');
    Route::get('/pembelian/{purchase}', [PurchaseController::class, 'show'])->name('purchases.show');
    Route::post('/pembelian/{purchase}/batal', [PurchaseController::class, 'cancel'])
        ->middleware('can:cancel-transaction')
        ->name('purchases.cancel');

    // --- Penjualan (stock-out).
    Route::get('/penjualan', [SaleController::class, 'index'])->name('sales.index');
    Route::get('/penjualan/tambah', [SaleController::class, 'create'])->name('sales.create');
    Route::post('/penjualan', [SaleController::class, 'store'])->name('sales.store');
    Route::get('/penjualan/{sale}', [SaleController::class, 'show'])->name('sales.show');
    Route::post('/penjualan/{sale}/batal', [SaleController::class, 'cancel'])
        ->middleware('can:cancel-transaction')
        ->name('sales.cancel');
});
