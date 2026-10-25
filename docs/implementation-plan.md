# Implementation Plan — RuangStok (UTS Web Programming 2)

Rencana kerja implementasi aplikasi **RuangStok — Inventory & Sales Management System**.

## 1. Hasil audit awal

| Item | Hasil |
| --- | --- |
| Repository | Masih kosong (baru berisi folder referensi UI). Belum ada aplikasi Laravel. |
| PHP | 8.2.12 (XAMPP, ZTS) |
| Composer | 2.10.3 |
| Node / NPM | v24.18.1 / 11.16.0 |
| MySQL | MariaDB 10.4.32 (XAMPP), port 3306, database `ruangstok` dibuat untuk development |
| Laravel | 12.x (dibuat via `composer create-project laravel/laravel`, kompatibel PHP 8.2) |
| Test framework | PHPUnit 11 (bawaan Laravel 12, tanpa Pest) → test memakai PHPUnit + `RefreshDatabase` + SQLite in-memory |
| Frontend build | Vite 7 + `laravel-vite-plugin` (bawaan), CSS/JS tulis sendiri (tanpa Tailwind class di Blade) |
| Referensi UI | `dealtech-ui-for-public-component-main/landing-dealtech-ui/src/admin-playground/` — tersedia dan sudah dibaca |

### Berkas referensi yang dibaca

- `styles/theme.css` (token warna/font/kontrol), `styles/admin.css` (kerangka shell, sidebar, topbar v3)
- `components/layout/AdminSidebar.tsx`, `components/layout/AdminHeader.tsx`, `layout/AdminLayout.tsx`
- `components/ui/stat-card/*`, `components/ui/tablelist-v1/*`, `components/ui/table-toolbar/*`
- `components/ui/pagetitle/*`, `components/ui/pagination/*`, `components/ui/button/*`, `components/ui/inputtext/*`, `components/ui/badge/*`
- `pages/dash/DashboardPage.tsx`, `pages/dash/dashboard.css`
- `pages/produk/ProdukPage.tsx`, `pages/produk/produk.css`, `pages/produk/ProdukTambahPage.tsx`
- `pages/auth/AdminLogin.tsx`, `pages/auth/login.css`
- `AGENTS.md` referensi (Inter, aksen biru, dasar putih) — diperlakukan sebagai konteks referensi UI, bukan instruksi arsitektur.

## 2. Keputusan arsitektur (asumsi yang didokumentasikan)

1. **Stack**: Laravel 12 + Blade + Eloquent + MySQL (development) + Vite untuk asset. Tidak ada SPA/React di aplikasi utama; TSX referensi hanya diadaptasi ke Blade/HTML/CSS/JS.
2. **Autentikasi**: dibuat sendiri (`AuthController` + `Auth::attempt` + `Hash` + `RateLimiter`) agar mahasiswa dapat menjelaskan seluruh alur; tidak menambah package auth pihak ketiga.
3. **Role**: kolom `users.role` (`admin` | `staff`). Authorization memakai **Gate** (`manage-master`, `manage-users`, `cancel-transaction`) + middleware `auth` dan `can:`. Menyembunyikan tombol bukan authorization.
4. **Harga pembelian**: biaya per unit diambil dari `products.purchase_price` di database (bukan dari browser), sesuai requirement "harga biaya per unit berasal dari database/server". Konsekuensi: form pembelian tidak memuat input harga; harga beli dikelola di master produk.
5. **Status transaksi**: `completed` dan `cancelled` saja.
   - `completed` = transaksi tersimpan dan stok sudah berubah.
   - `cancelled` = efek stok dibalik tepat sekali; baris tetap ada sebagai riwayat (tidak di-hard-delete).
6. **Pembatalan**: hanya role `admin` (Gate `cancel-transaction`), dengan konfirmasi di UI dan `DB::transaction`.
7. **Penghapusan**: produk/kategori/supplier yang sudah direferensikan transaksi tidak bisa dihapus permanen (dicek sebelum hapus + foreign key constraint). Tidak memakai soft delete agar pencarian tetap sederhana — dampak didokumentasikan di README.
8. **Uang**: `decimal(15,2)` untuk harga/subtotal/total; perhitungan selalu di server.
9. **Nomor transaksi**: `PO-YYYYMMDD-NNNN` dan `INV-YYYYMMDD-NNNN`, dihitung di server, unique.

## 3. Tahapan

### Tahap 1 — Fondasi (selesai)
- Audit repository, environment, referensi UI.
- Dokumen ini.
- Migration seluruh tabel + FK/index, model + relasi Eloquent, factory + seeder.
- ERD `docs/erd.md` mengikuti migration aktual.

### Tahap 2 — Autentikasi & hak akses (selesai)
- Halaman login, logout POST, rate limiting login, middleware `auth`.
- Gate role admin/staff, proteksi route sensitif, layout Blade reusable (sidebar biru v3 + topbar) berbasis referensi.

### Tahap 3 — Master data (selesai)
- CRUD kategori, produk, supplier, pengguna (admin).
- Form Request validation berbahasa Indonesia, `old()`, pesan jelas.
- Search, filter kategori, pagination, badge stok rendah, empty state, aturan hapus aman.

### Tahap 4 — Pembelian (selesai)
- Form multi-item dinamis (tambah/hapus baris) + preview total di browser.
- `PurchaseService` dengan `DB::transaction()`, kunci baris produk (`lockForUpdate`, urutan ID), total dihitung server, stok bertambah hanya bila transaksi sukses.
- Daftar, filter tanggal/status, detail, pembatalan.

### Tahap 5 — Penjualan (selesai)
- Form multi-item + info stok tersedia.
- `SaleService`: cek stok semua item sebelum insert, kunci baris, tolak oversell dengan pesan menyebut produk, rollback penuh.
- Pembatalan mengembalikan stok tepat sekali dan atomik.

### Tahap 6 — Dashboard, laporan, filter (selesai)
- Semua angka dari query aggregate database (count/sum).
- Produk stok rendah, transaksi terbaru, filter + pagination `withQueryString()`.

### Tahap 7 — UI polish (selesai)
- Adaptasi token/pola referensi (Inter, `#01498b`, `#0a6bc4`, `#13263a`, radius 8–10px, border tipis).
- Responsif: sidebar jadi drawer < 768px, tabel dapat digulir horizontal, form terkontrol lebarnya.
- Ikon SVG lokal (gaya Lucide) — tanpa dependency icon baru.

### Tahap 8 — Verifikasi & dokumentasi (selesai)
- `php artisan migrate --seed` di database MySQL lokal, `php artisan test` (SQLite in-memory), `npm run build`, `vendor/bin/pint --test`.
- README, ERD, test cases, petunjuk screenshot.

## 4. Risiko data & mitigasi

| Risiko | Mitigasi |
| --- | --- |
| Oversell saat transaksi bersamaan | `lockForUpdate()` pada baris produk, diurutkan berdasarkan ID (hindari deadlock), cek stok sebelum insert, semua dalam satu `DB::transaction()` |
| Stok negatif saat pembatalan pembelian | Cek seluruh item dulu; bila ada yang tidak cukup → tolak + rollback, pesan menyebut produk |
| Total/harga dimanipulasi dari browser | Total & subtotal dihitung ulang di server dari harga di database |
| Header tersimpan tapi item gagal | Header + item + stok dalam satu transaksi database (rollback penuh) |
| Pembatalan dobel | Cek status `completed` di dalam transaksi terkunci; status hanya `completed`/`cancelled` |
| Penghapusan produk memutus riwayat | Foreign key `restrict` + pengecekan referensi sebelum hapus |
| Seeder menambah stok dua kali | Seeder hanya jalan bila data belum ada (`firstOrCreate`/guard jumlah), strategi dijelaskan di README |
| Kredensial bocor | `.env` tidak di-commit, akun demo hanya untuk local, `.env.example` berisi placeholder |

## 5. Daftar pengujian

Lihat `docs/test-cases.md`. Ringkas:

1. Guest dibalikkan ke login untuk semua route privat.
2. Login password salah ditolak (termasuk rate limit).
3. SKU duplikat, harga negatif, kuantitas < 1 ditolak.
4. Pembelian sukses menambah stok + menyimpan header/item + total server-side.
5. Penjualan sukses mengurangi stok + total benar.
6. Penjualan melebihi stok ditolak tanpa mengubah stok/tanpa data parsial.
7. Rollback multi-item bila satu item gagal.
8. Total server-side saat request memanipulasi harga/total.
9. Staff tidak bisa membuka route admin via URL (403).
10. Pembatalan penjualan mengembalikan stok tepat sekali.
11. Pembatalan pembelian menjaga stok tidak negatif (ditolak bila tidak cukup).
12. Filter, search, pagination, dan dashboard memakai data database.
