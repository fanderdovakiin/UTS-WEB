# Instruksi Pengambilan Screenshot

> Catatan: capture otomatis sudah dicoba berulang kali pada 22 Okt 2026 melalui
> browser terintegrasi (termasuk `browser.screenshot` full-page) tetapi lingkungan
> berjalan headless tanpa jendela desktop, sehingga capture gagal. **Tidak ada
> screenshot palsu yang dibuat** — berkas di bawah ini harus diambil manual
> sesuai langkah berikut.

Environment pengembangan tidak memiliki jendela desktop yang bisa di-capture secara
otomatis, sehingga screenshot di folder ini **harus diambil manual** dengan
perintah-perintah berikut. Jalankan dulu aplikasinya:

```bash
php artisan migrate:fresh --seed   # opsional: reset + data demo
php artisan serve
npm run build                      # aset CSS/JS produksi
```

Buka `http://127.0.0.1:8000`, lalu ambil screenshot **full-page** (mis. Ctrl+Shift+S
di Firefox, atau DevTools → Command menu → "Capture full size screenshot").
Gunakan lebar jendela ± 1440 px. Simpan dengan nama persis seperti daftar di bawah.

| Berkas | Halaman | Peran | Yang harus terlihat |
|---|---|---|---|
| `01-login.png` | `http://127.0.0.1:8000/login` | — (belum login) | Form login RuangStok dengan kartu informasi fitur di sampingnya |
| `02-dashboard.png` | `/dashboard` | Admin (`owner@ruangstok.test` / `ruangstok123`) | Kartu ringkasan (produk, penjualan bulan ini, pembelian bulan ini, pengguna), tabel transaksi terbaru, tabel stok rendah |
| `03-produk.png` | `/produk` | Admin | Tabel produk dengan pencarian, filter kategori/stok, badge "rendah" |
| `04-produk-stok-rendah.png` | `/stok-rendah` | Admin | Laporan 3 produk stok rendah dari data seeder |
| `05-form-produk.png` | `/produk/tambah` | Admin | Form tambah produk (SKU, harga beli/jual, stok awal, stok minimum) |
| `06-pembelian.png` | `/pembelian` | Admin | Daftar pembelian beserta filter status/tanggal |
| `07-form-pembelian.png` | `/pembelian/tambah` | Admin atau Staff | Form transaksi multi-item: pilih pemasok, beberapa baris produk + qty, subtotal per baris, tombol "Tambah Item" |
| `08-detail-pembelian.png` | `/pembelian/{nomor}` (klik salah satu baris) | Admin | Informasi transaksi, tabel item, tombol "Batalkan Transaksi" |
| `09-penjualan.png` | `/penjualan` | Admin | Daftar penjualan |
| `10-form-penjualan.png` | `/penjualan/tambah` | Admin atau Staff | Form penjualan multi-item dengan ringkasan total di bawah |
| `11-detail-penjualan.png` | `/penjualan/{nomor}` (klik salah satu baris) | Admin | Tabel item terjual, total server-side, tombol pembatalan |
| `12-detail-penjualan-dibatalkan.png` | Halaman yang sama setelah menekan "Batalkan Transaksi" dan konfirmasi | Admin | Alert pembatalan, badge "Sudah dibatalkan — stok sudah dikembalikan", tombol pembatalan hilang |
| `13-kategori-pemasok.png` | `/kategori` lalu `/pemasok` (gabungkan atau ambil terpisah `13a`, `13b`) | Admin | Master data kategori dan pemasok |
| `14-pengguna.png` | `/pengguna` | Admin | Tabel pengguna dengan role Admin/Staff dan jumlah transaksi |
| `15-staff-dashboard.png` | `/dashboard` | Staff (`staff@ruangstok.test` / `ruangstok123`) | Sidebar tanpa menu master data & pengguna; dashboard tetap tampil |
| `16-oversell.png` | `/penjualan/tambah`, isi 2 baris produk — satu dengan qty melebihi stok, lalu simpan | Staff | Alert merah "Penjualan ditolak karena stok tidak mencukupi…" dan form kembali tanpa data tersimpan |

Setelah semua berkas ada, daftarkan di `README.md` bagian Tangkapan Layar.
