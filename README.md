# RuangStok — Sistem Manajemen Inventori & Penjualan

RuangStok adalah aplikasi web untuk tugas UTS Pemrograman Web . Aplikasi ini mengelola produk, kategori, pemasok, pembelian, penjualan, dan stok menggunakan **Laravel 12, Blade, dan MySQL**.

## Fitur Utama

- **Login dan hak akses:** Admin/Owner dan Staff memiliki izin berbeda.
- **Master data:** pengelolaan produk, kategori, pemasok, dan pengguna.
- **Pembelian multi-item:** transaksi berhasil menambah stok secara otomatis.
- **Penjualan multi-item:** transaksi memeriksa stok dan menolak penjualan jika stok tidak mencukupi.
- **Konsistensi transaksi:** perhitungan total dilakukan di server; perubahan data menggunakan transaksi database agar kegagalan tidak meninggalkan data parsial.
- **Pembatalan transaksi:** hanya pengguna berwenang yang dapat membatalkan; perubahan stok dipulihkan sesuai aturan.
- **Dashboard dan stok rendah:** menampilkan ringkasan data serta produk yang perlu ditambah stoknya.
- **Pencarian dan filter:** dilengkapi pagination serta detail transaksi.

## Teknologi

- PHP 8.2 atau lebih baru dan Laravel 12
- Blade, CSS/JavaScript, dan Vite
- MySQL atau MariaDB
- PHPUnit untuk pengujian

## Instalasi

### Prasyarat

Siapkan PHP 8.2+, Composer, Node.js 18+, MySQL/MariaDB, serta ekstensi PHP `pdo_mysql`, `pdo_sqlite`, `mbstring`, dan `bcmath`.

### Langkah menjalankan aplikasi

1. Clone repository dan masuk ke folder proyek:

   ```bash
   git clone https://github.com/fanderdovakiin/UTS-WEB.git
   cd UTS-WEB
   ```

2. Salin `.env.example` menjadi `.env`. Di Windows:

   ```bat
   copy .env.example .env
   ```

   Di macOS/Linux:

   ```bash
   cp .env.example .env
   ```

3. Pasang dependency PHP:

   ```bash
   composer install
   ```

4. Buat database kosong bernama `ruangstok` di MySQL/MariaDB, lalu sesuaikan pengaturan `DB_*` di file `.env`.

5. Buat application key dan jalankan migration serta seeder:

   ```bash
   php artisan key:generate
   php artisan migrate --seed
   ```

6. Pasang dan build aset frontend:

   ```bash
   npm install
   npm run build
   ```

7. Jalankan aplikasi:

   ```bash
   php artisan serve
   ```

   Buka http://127.0.0.1:8000 di browser.

> **Catatan:** `php artisan migrate --seed` digunakan untuk menyiapkan database baru. Jangan gunakan `migrate:fresh` pada database yang berisi data penting karena perintah tersebut menghapus tabel sebelum membuatnya kembali.

## Akun Demo

Akun berikut disediakan oleh seeder untuk penggunaan lokal/demo.

| Role | Email | Password |
|---|---|---|
| Admin/Owner | `owner@ruangstok.test` | `ruangstok123` |
| Admin | `admin2@ruangstok.test` | `ruangstok123` |
| Staff | `staff@ruangstok.test` | `ruangstok123` |

Jangan gunakan akun demo ini untuk deployment produksi.

## Pengujian

Jalankan seluruh test dengan perintah:

```bash
php artisan test
```

Pada hasil pengujian terakhir yang tercatat, terdapat **66 test dan 278 assertions**. Daftar skenario pengujian tersedia di [`docs/test-cases.md`](docs/test-cases.md).

## Dokumentasi

- [ERD dan relasi database](docs/erd.md)
- [Skenario pengujian](docs/test-cases.md)

## Screenshot Aplikasi

Dokumentasi visual aplikasi tersedia di folder [`screenshot/`](screenshot/).
