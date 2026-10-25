# Test Cases — RuangStok

Seluruh test berjalan otomatis dengan **PHPUnit 11** di atas **SQLite in-memory**
(konfigurasi di `phpunit.xml`), terpisah penuh dari database MySQL development.
Jalankan dengan:

```bash
php artisan test
```

Status terakhir saat dokumentasi ditulis: **66 test lolos, 278 assertions** (lihat
bagian akhir untuk rincian jumlah assertion).

## Pemetaan skenario wajib → test

| # | Senario wajib | Test |
|---|---|---|
| 1 | Halaman dilindungi login (guest redirect) | `AuthenticationTest::test_tamu_dialihkan_ke_halaman_login`, `AuthorizationTest::test_tamu_tidak_bisa_mengakses_halaman_apapun` |
| 2 | Login gagal (password salah) + rate limiting percobaan login | `AuthenticationTest::test_login_gagal_dengan_password_salah`, `AuthenticationTest::test_login_dibatasi_setelah_banyak_percobaan_gagal` |
| 3 | Login sukses & logout | `AuthenticationTest::test_login_berhasil_dengan_kredensial_valid`, `test_logout_mengakhiri_sesi` |
| 4 | SKU duplikat ditolak | `ProductTest::test_sku_tidak_boleh_duplikat` |
| 5 | Harga/stok/qty negatif ditolak | `ProductTest::test_harga_dan_stok_negatif_ditolak`, `PurchaseTest::test_kuantitas_negatif_ditolak_validasi` |
| 6 | Pembelian menambah stok semua item | `PurchaseTest::test_pembelian_menambah_stok_semua_item` |
| 7 | Penjualan mengurangi stok semua item | `SaleTest::test_penjualan_mengurangi_stok_semua_item` |
| 8 | Oversell ditolak **tanpa data sebagian** (rollback penuh) | `SaleTest::test_oversell_ditolak_tanpa_data_sebagian` |
| 9 | Multi-item: satu item gagal = seluruh transaksi batal | `SaleTest::test_oversell_ditolak_tanpa_data_sebagian` (item sah ikut tidak tersimpan), `PurchaseTest::test_item_tidak_valid_membatalkan_seluruh_transaksi` |
| 10 | Total dihitung server; input total/harga browser dipalsukan → diabaikan | `SaleTest::test_harga_jual_dihitung_server_bukan_dari_browser`, `PurchaseTest::test_total_dihitung_server_bukan_dari_browser` |
| 11 | Role staff ditolak (403) di rute admin, diverifikasi lewat akses URL langsung | `AuthorizationTest::test_staff_tidak_bisa_mengakses_rute_master_data`, `test_staff_tidak_bisa_mengubah_atau_menghapus_produk`, `test_staff_tidak_bisa_mengelola_pengguna`, `test_staff_tidak_bisa_membatalkan_transaksi` |
| 12 | Pembatalan penjualan mengembalikan stok **tepat sekali** (pembatalan kedua ditolak) | `SaleTest::test_pembatalan_mengembalikan_stok_tepat_satu_kali` |
| 13 | Pembatalan pembelian membalik stok; stok tidak pernah negatif; gagal = rollback | `PurchaseTest::test_pembatalan_mengurangi_stok_persis_satu_kali`, `PurchaseTest::test_pembatalan_ditolak_jika_stok_sudah_dipakai_transaksi_lain` |
| 14 | Pencarian, filter, pagination | `ProductTest::test_pencarian_dan_filter_stok_bekerja`, `test_pagination_produk`, `MasterDataTest::test_filter_status_dan_pencarian_transaksi`, `test_filter_rentang_tanggal_penjualan` |
| 15 | Dashboard menampilkan angka dari database (bukan hard-coded) | `DashboardTest::test_dashboard_menampilkan_angka_asli_dari_database`, `test_dashboard_kosong_tanpa_data`, `test_dashboard_dihitung_dari_status_selesai_saja` |
| 16 | **Visibilitas transaksi per role**: staff tidak bisa membuka transaksi orang lain dengan mengganti ID di URL; admin melihat semua | `TransactionAccessTest` (7 test — lihat rincian di bawah) |
| 17 | **Presisi uang desimal** (bukan floating-point) | `MoneyTest` (5 test unit) + `SaleTest`/`PurchaseTest::test_subtotal_dan_total_*_presisi_desimal` |
| 18 | **Penomoran aman saat transaksi bersamaan** | `SaleTest`/`PurchaseTest::test_tabrakan_nomor_saat_transaksi_bersamaan_ditangani_dengan_retry` |
| 19 | **Form edit produk** dibuka via URL langsung oleh staff → ditolak server (`can:manage-master`), bukan sekadar tombol tersembunyi | `AuthorizationTest::test_staff_tidak_bisa_mengubah_atau_menghapus_produk` (assert 403 untuk `products.edit`), `test_admin_bisa_mengakses_semua_rute_admin` (admin 200) |
| 20 | **Riwayat produk & pemasok** tidak membocorkan nomor/tanggal/jumlah/subtotal/tautan transaksi orang lain ke staff | `TransactionAccessTest::test_riwayat_produk_tidak_membocorkan_transaksi_orang_lain`, `test_riwayat_pemasok_tidak_membocorkan_pembelian_orang_lain` |
| 21 | Route resource kategori & pengguna **tanpa `show`** — tidak ada nama rute maupun URL yang menunjuk ke method tak tersedia | `AuthorizationTest::test_rute_detail_kategori_dan_pengguna_tidak_didaftarkan` |
| 22 | **Filter tanggal & status divalidasi** sebelum masuk query (nilai tak valid diabaikan tanpa error) | `MasterDataTest::test_filter_tanggal_dan_status_tidak_valid_diabaikan` |

## Rincian: visibilitas transaksi per role (`TransactionAccessTest`)

Aturan yang ditegakkan di server: **Admin/Owner melihat semua transaksi; Staff hanya
transaksi yang ia catat sendiri** — berlaku pada daftar, detail, pencarian, filter,
dan angka dashboard.

| Test | Yang dibuktikan |
|---|---|
| `test_staff_tidak_bisa_membuka_detail_penjualan_orang_lain_via_url` | ID diganti di URL → **403**; pemiliknya sendiri → 200 |
| `test_staff_tidak_bisa_membuka_detail_pembelian_orang_lain_via_url` | ID diganti di URL → **403**; admin → 200 |
| `test_daftar_penjualan_staff_hanya_berisi_miliknya_sendiri` | Query daftar ter-scope `user_id` |
| `test_daftar_pembelian_staff_hanya_berisi_miliknya_sendiri` | Query daftar ter-scope `user_id` |
| `test_pencarian_staff_tidak_bisa_menembus_ke_transaksi_lain` | Mencari nomor milik orang lain → 0 baris hasil |
| `test_admin_melihat_seluruh_transaksi` | Admin melihat semua daftar + detail siapa pun |
| `test_dashboard_staff_hanya_menghitung_transaksi_miliknya` | Nilai bulanan, jumlah, dan daftar terbaru staff = miliknya saja |

## Rincian: presisi uang (`MoneyTest` + feature)

| Kasus | Nilai |
|---|---|
| `1.15 × 100` | `115.00` (float biasa: 114.99999999999999) |
| `3333.33 × 3` | `9999.99` (float biasa: 9999.989999999998) |
| `0.10 + 0.20` | `0.30` (float biasa: 0.30000000000000004) |
| Penjualan 3 × Rp 3.333,33 + 100 × Rp 1,15 | Subtotal item dan total `Rp 10.114,99` persis di database |
| Pembelian 3 × Rp 3.333,33 + 4 × Rp 1.250,55 | `9999.99` + `5002.20` = total `15002.19` persis |

## Rincian: penomoran aman konkurensi

Mekanisme: UNIQUE constraint di database (nomor ganda tidak pernah tersimpan) +
retry otomatis dengan nomor berikutnya (maks. 3 percobaan) bila duplicate-key terjadi.
Test **mensimulasikan tabrakan nyata**: subclass service dipaksa memakai nomor yang
sudah tersimpan pada percobaan pertama → duplicate-key → rollback → retry → nomor
berikutnya dipakai, transaksi lama tidak rusak, stok tetap konsisten.

## Skenenario tambahan yang juga diuji

| Skenario | Test |
|---|---|
| Staff tetap boleh melihat produk/pemasok dan mencatat transaksi | `AuthorizationTest::test_staff_boleh_melihat_produk_dan_mencatat_transaksi`, `PurchaseTest::test_staff_dapat_mencatat_pembelian`, `SaleTest::test_staff_dapat_mencatat_penjualan` |
| Stok tidak bisa diubah lewat form master produk | `ProductTest::test_stok_tidak_bisa_diubah_lewat_form_master` |
| Produk dalam riwayat transaksi tidak bisa dihapus; yang belum dipakai bisa | `ProductTest::test_produk_dalam_riwayat_transaksi_tidak_bisa_dihapus`, `test_produk_bisa_dihapus_jika_belum_pernah_ditransaksikan` |
| Dua baris produk sama pada satu form digabung sebelum dicek stok | `SaleTest::test_dua_baris_produk_sama_digabung_sebelum_dicek_stok` |
| Nomor urut transaksi (`PO-…`/`INV-…`) berurutan per tanggal | `PurchaseTest::test_nomor_urut_berurutan_per_tanggal`, `SaleTest::test_nomor_urut_berurutan_per_tanggal` |
| CRUD kategori; kategori berisi produk tidak bisa dihapus | `MasterDataTest::test_kategori_crud_lengkap`, `test_kategori_punya_produk_tidak_bisa_dihapus` |
| Akun staff baru bisa login; akun sendiri & admin terakhir terlindungi | `MasterDataTest::test_admin_dapat_menambah_akun_staff_baru_yang_bisa_login`, `test_admin_tidak_bisa_menghapus_diri_sendiri`, `test_admin_terakhir_tidak_bisa_diturunkan_atau_dihapus` |

## Verifikasi manual (bukan PHPUnit)

Dijalankan terhadap server development dengan database MySQL berisi data seeder:

1. Login `owner@ruangstok.test` → dashboard menampilkan 12 produk, 3 stok rendah,
   dan Rp 4.237.000 penjualan bulan ini (sesuai data seeder).
2. Mencatat penjualan 3 unit "Air Mineral 600ml" lewat form browser →
   nomor `INV-20261018-0001`, total **Rp 135.000** (3 × Rp 45.000 dihitung server),
   stok produk 38 → 35.
3. Membatalkan penjualan tersebut → stok kembali **38**, status "Dibatalkan",
   tombol pembatalan hilang (tidak bisa diulang), riwayat baris tetap tersimpan.
4. Semua halaman (`/produk`, `/pembelian`, `/penjualan`, `/kategori`, `/pemasok`,
   `/pengguna`, `/stok-rendah`, form tambah) dimuat di browser: **nol error console**.

## Catatan teknis

- `lockForUpdate` diabaikan secara aman oleh SQLite (uji fungsional stok tetap valid
  karena semua pemeriksaan dilakukan di dalam satu transaksi database), dan aktif
  penuh di MySQL untuk production.
- Rate limiting login memakai `RateLimiter` dengan kunci `email|IP`, maksimal
  5 percobaan gagal per menit, dengan pesan error bahasa Indonesia.
- Sebelum suite dijalankan, `RefreshDatabase` membuat schema SQLite in-memory segar
  tiap test — tidak ada test yang saling memengaruhi.
