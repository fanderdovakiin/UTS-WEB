# RuangStok — Sistem Manajemen Inventori & Penjualan

Aplikasi web studi kasus **UTS Pemrograman Web 2**: pengelolaan produk, pembelian
(stock-in), dan penjualan (stock-out) dengan penghitungan stok yang aman secara
transaksi (atomic) di atas **Laravel 12 + Blade + MySQL**.

Seluruh label, pesan, dan tampilan aplikasi memakai **bahasa Indonesia** dengan
format mata uang **Rupiah**.

## Fitur

- **Autentikasi** kustom (login/logout, rate limiting percobaan login, sesi tervalidasi).
- **Dua role**: Admin/Owner (kelola master data, pengguna, melihat **semua** transaksi,
  dan membatalkan transaksi) dan Staff (melihat produk, melihat serta mencatat
  transaksi miliknya sendiri). Aturan visibilitas ditegakkan di **server** — pada query
  daftar, guard halaman detail (mengganti ID di URL ditolak dengan 403), pencarian,
  filter, dan angka dashboard — bukan sekadar menyembunyikan menu di Blade.
- **Master data**: produk, kategori, pemasok — CRUD penuh, pencarian, filter, pagination.
- **Pembelian multi-item**: memilih beberapa produk + kuantitas sekaligus; stok bertambah
  dan total dihitung server dari harga beli produk di database.
- **Penjualan multi-item**: stok dicek ulang untuk **seluruh** item tepat sebelum disimpan;
  jika satu item tidak mencukupi, **seluruh transaksi dibatalkan (rollback)** — tidak ada
  data sebagian dan stok tidak pernah menjadi negatif (oversell ditolak).
- **Pembatalan transaksi (khusus admin, dengan konfirmasi)**: membalik efek stok secara
  atomik **tepat sekali**; baris riwayat tetap tersimpan. Pembatalan pembelian ditolak
  (dengan rollback) bila stok sudah dipakai transaksi lain sehingga tidak bisa dibalik.
- **Dashboard** dengan angka agregat langsung dari database (produk, nilai pembelian &
  penjualan bulan ini, transaksi terbaru, produk stok rendah).
- **Laporan stok rendah** (`stock <= minimum stock`) dengan jumlahnya di sidebar.
- **Manajemen pengguna & role** (admin): tambah/ubah akun, proteksi akun sendiri,
  proteksi admin terakhir, riwayat transaksi memblokir penghapusan.

## Teknologi

| Komponen | Pilihan |
|---|---|
| Framework | Laravel 12 (PHP ≥ 8.2) |
| View | Blade + komponen anonim (`x-icon`, `x-page-title`, `x-rupiah`, `x-stat-card`) |
| Database | MySQL/MariaDB (development), SQLite in-memory (otomatis untuk test) |
| Aset | Vite + CSS/JS custom (tanpa Tailwind, tanpa icon library eksternal) |
| Test | PHPUnit 11 (`php artisan test`) |
| Style | Laravel Pint (`vendor/bin/pint`) |

## Pemasangan

Prasyarat: **PHP ≥ 8.2** (ekstensi `pdo_mysql`, `sqlite3`, `mbstring`, `bcmath`), **Composer**,
**Node.js ≥ 18**, dan server **MySQL/MariaDB**.

```bash
# 1. Salin berkas lingkungan lalu isi sesuai mesin Anda
copy .env.example .env        # Windows: copy | Unix: cp

# 2. Dependensi PHP & kunci aplikasi
composer install
php artisan key:generate

# 3. Database — buat database MySQL kosong bernama ruangstok, sesuaikan
#    DB_* di .env, lalu:
php artisan migrate:fresh --seed

# 4. Aset CSS/JS
npm install
npm run build

# 5. Jalankan
php artisan serve             # http://127.0.0.1:8000
```

## Akun demo (dibuat oleh seeder)

| Role | Email | Password |
|---|---|---|
| Admin/Owner | `owner@ruangstok.test` | `ruangstok123` |
| Admin | `admin2@ruangstok.test` | `ruangstok123` |
| Staff | `staff@ruangstok.test` | `ruangstok123` |

Seeder idempoten (memakai `firstOrCreate` + penjagaan), aman dijalankan ulang tanpa
menggandakan data transaksi.

## Menjalankan test

```bash
php artisan test
```

66 test (278 assertions) berjalan di SQLite in-memory — terpisah penuh dari database
MySQL development. Pemetaan skenario wajib ada di [`docs/test-cases.md`](docs/test-cases.md).

## Dokumentasi

- [`docs/implementation-plan.md`](docs/implementation-plan.md) — rencana & struktur aplikasi
- [`docs/erd.md`](docs/erd.md) — ERD (Mermaid) sesuai migrasi nyata
- [`docs/test-cases.md`](docs/test-cases.md) — skenario uji & hasil
- [`docs/screenshots/README.md`](docs/screenshots/README.md) — instruksi pengambilan screenshot

## Catatan desain & UI

Antarmuka diadaptasi dari referensi UI **DealTech**
(`dealtech-ui-for-public-component-main/landing-dealtech-ui/src/admin-playground/`):
tipografi Inter (font lokal, di-serve dari `public/fonts/`, tanpa CDN), permukaan putih,
aksen biru, bayangan sangat tipis, dan pola komponen panel/tabel/toolbar. Adaptasi
dilakukan ke **Blade + CSS/JS murni** (tidak menyalin komponen React/TSX), halaman
**login menjadi halaman awal** (bukan landing page), dan seluruh branding memakai
nama **RuangStok**.

## Keputusan desain penting

- **Harga tidak dipercaya dari browser**: total transaksi dan harga per item selalu
  dihitung ulang server dari `products.purchase_price` / `products.selling_price`.
- **Perhitungan uang presisi-desimal (bcmath)**: perkalian harga × kuantitas dan
  penjumlahan total memakai `App\Support\Money` (scale 2) sehingga tidak bergantung
  pada floating-point IEEE 754 — teruji lewat test presisi (mis. `1.15 × 100 = 115.00`).
- **Penomoran transaksi aman saat bersamaan**: `purchase_number`/`sale_number` dihitung
  server per tanggal dengan UNIQUE constraint di database — nomor ganda tidak pernah
  tersimpan. Bila dua proses menghasilkan nomor sama, transaksi rollback penuh lalu
  dicoba ulang dengan nomor berikutnya (maksimal 3 kali). Mekanisme ini teruji oleh
  test simulasi tabrakan nomor, bukan sekadar klaim.
- **Visibilitas transaksi per role**: admin melihat semua transaksi; staff hanya
  transaksi yang ia catat sendiri (daftar, detail, pencarian, dan dashboard).
- **Stok awal diisi saat produk dibuat**, dan perubahan stok berikutnya hanya melalui
  transaksi yang valid: pembelian (tambah), penjualan (kurang), serta pembatalan
  (pembalikan). Form ubah produk mengabaikan field stok sehingga setiap perubahan stok
  selalu dapat dipertanggungjawabkan lewat riwayat transaksi.
- **Kunci baris berurutan** (`lockForUpdate` diurutkan ID produk) untuk mencegah deadlock
  antar transaksi bersamaan.
- **Produk yang sudah ditransaksikan tidak bisa dihapus** (dicek di controller + FK
  `restrictOnDelete`), sehingga riwayat selalu dapat ditelusuri.
- **Tanpa soft delete**: pembatalan transaksi adalah proses pembalikan stok yang atomik,
  bukan penghapusan data — sesuai kebutuhan tugas.

## Tangkapan layar

Screenshot diambil manual sesuai [`docs/screenshots/README.md`](docs/screenshots/README.md)
(karena environment pengembangan tidak memiliki jendela desktop yang bisa di-capture
otomatis).
