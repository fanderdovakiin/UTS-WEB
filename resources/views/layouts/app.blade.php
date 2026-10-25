<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('judul', 'Dashboard') · RuangStok</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>
    <div class="app-shell">
        <div id="sidebar-overlay" class="sidebar-overlay" data-tutup-sidebar></div>

        <aside id="app-sidebar" class="app-sidebar" aria-label="Navigasi utama">
            <div class="sidebar-header">
                <a href="{{ route('dashboard') }}" class="sidebar-brand" data-tutup-sidebar>
                    <span class="sidebar-brand__mark">R</span>
                    <span class="sidebar-brand__text">RuangStok</span>
                </a>
                <button type="button" class="sidebar-close-btn" data-tutup-sidebar aria-label="Tutup menu">
                    <x-icon name="tutup" />
                </button>
            </div>

            <nav class="sidebar-nav-scroll">
                <ul class="sidebar-menu">
                    <li>
                        <a href="{{ route('dashboard') }}"
                           class="sidebar-menu-btn {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                            <x-icon name="dashboard" />
                            <span>Dashboard</span>
                        </a>
                    </li>
                </ul>

                <div class="sidebar-section-title">Transaksi</div>
                <ul class="sidebar-menu">
                    <li>
                        <a href="{{ route('purchases.index') }}"
                           class="sidebar-menu-btn {{ request()->routeIs('purchases.*') ? 'active' : '' }}">
                            <x-icon name="pembelian" />
                            <span>Pembelian</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('sales.index') }}"
                           class="sidebar-menu-btn {{ request()->routeIs('sales.*') ? 'active' : '' }}">
                            <x-icon name="penjualan" />
                            <span>Penjualan</span>
                        </a>
                    </li>
                </ul>

                <div class="sidebar-section-title">Master Data</div>
                <ul class="sidebar-menu">
                    <li>
                        <a href="{{ route('products.index') }}"
                           class="sidebar-menu-btn {{ request()->routeIs('products.*') && !request()->routeIs('products.low-stock') ? 'active' : '' }}">
                            <x-icon name="produk" />
                            <span>Produk</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('suppliers.index') }}"
                           class="sidebar-menu-btn {{ request()->routeIs('suppliers.*') ? 'active' : '' }}">
                            <x-icon name="pemasok" />
                            <span>Pemasok</span>
                        </a>
                    </li>
                    @can('manage-master')
                        <li>
                            <a href="{{ route('categories.index') }}"
                               class="sidebar-menu-btn {{ request()->routeIs('categories.*') ? 'active' : '' }}">
                                <x-icon name="kategori" />
                                <span>Kategori</span>
                            </a>
                        </li>
                    @endcan
                </ul>

                <div class="sidebar-section-title">Laporan &amp; Akun</div>
                <ul class="sidebar-menu">
                    <li>
                        <a href="{{ route('products.low-stock') }}"
                           class="sidebar-menu-btn {{ request()->routeIs('products.low-stock') ? 'active' : '' }}">
                            <x-icon name="stok-rendah" />
                            <span>Stok Rendah</span>
                            @if ($jumlahStokRendah > 0)
                                <span class="sidebar-menu-badge">{{ $jumlahStokRendah }}</span>
                            @endif
                        </a>
                    </li>
                    @can('manage-users')
                        <li>
                            <a href="{{ route('users.index') }}"
                               class="sidebar-menu-btn {{ request()->routeIs('users.*') ? 'active' : '' }}">
                                <x-icon name="pengguna" />
                                <span>Pengguna</span>
                            </a>
                        </li>
                    @endcan
                </ul>
            </nav>

            <div class="sidebar-footer">
                <div class="sidebar-user-card">
                    <span class="sidebar-user-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                    <span style="min-width:0">
                        <span class="sidebar-user-name">{{ auth()->user()->name }}</span>
                        <span class="sidebar-user-email">
                            {{ auth()->user()->isAdmin() ? 'Admin / Owner' : 'Staff' }} · {{ auth()->user()->email }}
                        </span>
                    </span>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="sidebar-logout-btn">
                        <x-icon name="keluar" />
                        <span>Keluar</span>
                    </button>
                </form>
            </div>
        </aside>

        <div style="display:flex; min-width:0; flex:1; flex-direction:column; overflow:hidden">
            <header class="app-topbar">
                <div class="app-topbar__kiri">
                    <button type="button" class="app-topbar__burger" data-buka-sidebar aria-label="Buka menu">
                        <x-icon name="menu" />
                    </button>
                    <span class="app-topbar__merek">RuangStok</span>
                    @hasSection('judul')
                        <span class="app-topbar__pemisah" aria-hidden="true"></span>
                        <span class="app-topbar__halaman">@yield('judul')</span>
                    @endif
                </div>

                <div class="app-topbar__kanan">
                    <time class="app-topbar__jam" id="topbar-jam">
                        <span class="app-topbar__jam-angka" data-jam-angka>--.--.--</span>
                        <span data-jam-tanggal></span>
                        <span class="app-topbar__jam-zona" data-jam-zona></span>
                    </time>

                    <div class="app-topbar__profil">
                        <button type="button" class="app-topbar__profil-tombol" data-profil-tombol
                                aria-haspopup="menu" aria-expanded="false"
                                aria-label="Menu akun {{ auth()->user()->name }}">
                            <span class="app-topbar__profil-avatar">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </span>
                            <x-icon name="bawah" :size="14" />
                        </button>

                        <div class="app-topbar__panel" id="profil-panel" role="menu">
                            <div class="app-topbar__panel-info">
                                <strong>{{ auth()->user()->name }}</strong>
                                <span>{{ auth()->user()->email }}</span>
                            </div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="app-topbar__panel-item" role="menuitem">
                                    <x-icon name="keluar" />
                                    <span>Keluar sekarang</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <main class="app-main">
                <div class="page">
                    @include('partials.flash')

                    @yield('content')
                </div>
            </main>
        </div>
    </div>
</body>

</html>
