<?php

namespace App\Providers;

use App\Models\Product;
use App\Models\User;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Semua izin role dicek di server lewat Gate ini (dipakai middleware can: di route).
        Gate::define('manage-master', fn (User $user) => $user->isAdmin());
        Gate::define('manage-users', fn (User $user) => $user->isAdmin());
        Gate::define('cancel-transaction', fn (User $user) => $user->isAdmin());

        // Pagination memakai tampilan kustom yang mengikuti desain tabel referensi.
        Paginator::defaultView('partials.pagination');

        // Badge stok rendah di sidebar dihitung sekali saat layout dirender,
        // bukan lewat query yang ditulis langsung di dalam Blade.
        View::composer('layouts.app', function ($view) {
            $view->with('jumlahStokRendah', Product::whereColumn('stock', '<=', 'minimum_stock')->count());
        });
    }
}
