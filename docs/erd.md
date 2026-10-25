# ERD — RuangStok (Entity Relationship Diagram)

Diagram di bawah ini dibuat **langsung dari migrasi yang ada di aplikasi**
(`database/migrations/`) sehingga selalu mencerminkan skema nyata.

```mermaid
erDiagram
    users {
        bigint id PK
        string name
        string email UK
        string password
        string role "admin | staff"
        string remember_token
        timestamps ""
    }

    categories {
        bigint id PK
        string name UK
        string description "nullable"
        timestamps ""
    }

    suppliers {
        bigint id PK
        string name UK
        string phone "nullable"
        string email "nullable"
        string address "nullable"
        timestamps ""
    }

    products {
        bigint id PK
        bigint category_id FK "restrictOnDelete"
        string sku UK
        string name
        decimal purchase_price "15,2 — harga beli"
        decimal selling_price "15,2 — harga jual"
        unsigned_int stock "hanya berubah lewat transaksi"
        unsigned_int minimum_stock
        string unit
        timestamps ""
    }

    purchases {
        bigint id PK
        bigint supplier_id FK "restrictOnDelete"
        bigint user_id FK "restrictOnDelete"
        string purchase_number UK "PO-YYYYMMDD-0001"
        decimal total "15,2"
        string status "completed | cancelled"
        string note "nullable"
        timestamp purchased_at
        timestamps ""
    }

    purchase_items {
        bigint id PK
        bigint purchase_id FK "cascadeOnDelete"
        bigint product_id FK "restrictOnDelete"
        unsigned_int quantity
        decimal unit_cost "15,2 — snapshot harga beli"
        decimal subtotal "15,2"
        timestamps ""
    }

    sales {
        bigint id PK
        bigint user_id FK "restrictOnDelete"
        string sale_number UK "INV-YYYYMMDD-0001"
        decimal total "15,2 — dihitung server"
        string status "completed | cancelled"
        string note "nullable"
        timestamp sold_at
        timestamps ""
    }

    sale_items {
        bigint id PK
        bigint sale_id FK "cascadeOnDelete"
        bigint product_id FK "restrictOnDelete"
        unsigned_int quantity
        decimal unit_price "15,2 — snapshot harga jual"
        decimal subtotal "15,2"
        timestamps ""
    }

    categories ||--o{ products : "memiliki"
    suppliers ||--o{ purchases : "memasok"
    users ||--o{ purchases : "mencatat (admin/staff)"
    users ||--o{ sales : "mencatat (admin/staff)"
    purchases ||--|{ purchase_items : "berisi"
    sales ||--|{ sale_items : "berisi"
    products ||--o{ purchase_items : "direferensikan"
    products ||--o{ sale_items : "direferensikan"
```

## Catatan desain

| Keputusan | Alasan |
|---|---|
| Kolom uang memakai `decimal(15,2)`, bukan `float` | Perhitungan rupiah tidak boleh kehilangan presisi. |
| `stock` hanya diisi saat pembuatan produk | Perubahan stok selanjutnya **hanya** lewat pembelian (tambah), penjualan (kurang), dan pembatalan (balik) — semua tercatat di riwayat transaksi. |
| `unit_cost` / `unit_price` di-snapshot ke tabel item | Riwayat harga tetap akurat walau harga master produk berubah di kemudian hari. |
| `status` hanya `completed` / `cancelled` | Pembatalan adalah proses **balik stok yang atomik**, bukan penghapusan data — baris transaksi tetap ada sebagai riwayat. |
| FK item ke produk memakai `restrictOnDelete` | Produk yang sudah pernah ditransaksikan tidak boleh dihapus; controller juga mengecek sebelum menghapus dengan pesan yang jelas. |
| FK header ke pemasok/pengguna memakai `restrictOnDelete` | Riwayat transaksi tidak boleh kehilangan pihak yang terlibat. |
| Nomor transaksi unik (`PO-…` / `INV-…`) | Dibangkitkan server per tanggal transaksi dengan penomoran berurutan, dicek ulang di database lewat constraint unique. |
