# Gambaran Aplikasi — Smart ERP PT TADCo Transport Perkasa

Dokumen ini menjelaskan **apa** aplikasi ini, **bagaimana** ia disusun, dan **di mana batas kemampuannya saat ini** (per 29 Oktober 2026). Isinya hasil audit langsung terhadap kode di `C:\laragon\www\ERP-Tadco`, bukan rencana.

- Repo: `https://github.com/chsryn/ERP-Tadco`
- Jenis: ERP distribusi barang (Laravel monolith, server-rendered)
- Domain bisnis: distribusi bahan pangan (terigu, dsb.) ke customer ritir/grosir dengan alur pesanan → pengiriman → tagihan → pembayaran, ditambah alur pembelian ke supplier/principal.

---

## 1. Ringkasan

Smart ERP TADCo adalah aplikasi web internal untuk mengelola:

1. **Master data** — customer, produk, supplier.
2. **Persediaan** — saldo stok per produk, kartu stok, penyesuaian manual.
3. **Penjualan (outbound)** — Delivery Order, Shipment, Invoice, Payment.
4. **Pembelian (inbound / SCM)** — Supplier Master, Data Purchasing produk, Purchase Order, rekomendasi Auto PO (draft), dokumen Confirm Order yang bisa dicetak, Purchase Receipt (penerimaan barang + tracking qty, tanpa Stock IN).
5. **Laporan** — stok, penjualan, piutang, pembayaran (dengan ekspor Excel).
6. **Asisten AI read-only** — ringkasan data sesuai role, tanpa kemampuan menulis.

ERP adalah **source of truth**. Lapisan AI hanya membaca dan menjelaskan, tidak pernah mengubah data.

Prinsip yang dipegang di repo ini:

- Penulisan stok hanya lewat jalur resmi (`StockService`, penyesuaian inventory, shipment), bukan dari modul lain.
- Transaksi multi-tabel dibungkus `DB::transaction` dengan `lockForUpdate()`.
- Identitas user selalu diambil dari session (`auth()->id()`), tidak pernah dari input pengguna/LLM.
- Uang disimpan `decimal(15,2)`, bukan float.

---

## 2. Teknologi

| Lapisan | Teknologi |
| --- | --- |
| Bahasa / runtime | PHP `^8.3` |
| Framework | Laravel `^13.8` |
| Database | MySQL (dev: Laragon `erp_tadco`), SQLite in-memory untuk test |
| Otorisasi | `spatie/laravel-permission ^8` (role: `admin`, `sales`, `finance`; permission Purchasing: 15, `admin` = semua) |
| Excel | `maatwebsite/excel` + `phpoffice/phpspreadsheet` |
| Tampilan | Blade + Bootstrap 5.3.3 (CDN) + Bootstrap Icons + `public/css/metronic.css`, JavaScript vanilla inline (tanpa build step di layout) |
| Test | PHPUnit 12, `RefreshDatabase`, feature test |
| Gaya kode | Laravel Pint |

Tidak ada SPA, tidak ada API publik, tidak ada queue worker wajib. Semua route web ada di `routes/web.php`.

---

## 3. Arsitektur

```text
routes/web.php
   │  middleware: auth, permission:<modul>.<aksi>, role:admin|sales|finance, throttle
   ▼
app/Http/Controllers/*          ← tipis: validasi request, panggil service, kembalikan view
   │  app/Http/Requests/*       ← validasi terstruktur (khusus modul tertentu)
   ▼
app/Services/*                  ← aturan bisnis + transaksi DB + lock
   │  app/Services/Ai/*         ← snapshot read-only untuk AI
   ▼
app/Models/* (Eloquent)         ← relasi, cast, sebagian logika model (Invoice: generate nomor, due date)
   ▼
database/migrations/*           ← skema
resources/views/* (Blade)       ← presentasi
```

Pola yang konsisten dipakai:

- **Thin controller.** Logika transaksi ada di service (`DeliveryOrderService`, `InvoiceService`, `PaymentService`, `PurchaseOrderService`, `StockService`).
- **FormRequest** dipakai untuk entitas yang punya aturan kompleks (`StorePurchaseOrderRequest`, `UpdatePurchaseOrderRequest`, `StoreSupplierRequest`, `UpdateSupplierRequest`, `AiChatRequest`). Modul lama memakai `$request->validate()` inline.
- **Layout tunggal** `resources/views/layouts/app.blade.php`: sidebar navy, topbar dengan pencarian global, area konten. Semua halaman mengikuti `@extends('layouts.app')` + `@section('content')`.
- **Naming route**: resource plural (`customers.index`), modul baru memakai snake_case (`purchase_orders.index`) dengan URL kebab (`/purchase-orders`).

---

## 4. Peta modul

| Modul | Entitas utama | Route | Akses |
| --- | --- | --- | --- |
| Autentikasi | `users` | `/login`, `/register`, `/logout` | guest / auth |
| Dashboard | agregat banyak tabel | `/dashboard` | auth |
| Pencarian global | customer + produk | `/search/global` | auth |
| Master Customer | `customers` | `/customers` | auth |
| Master Produk | `products`, `product_discounts` | `/products` | auth |
| Master Supplier (SCM-01) | `suppliers` | `/suppliers` | `permission:suppliers.*` |
| Purchase Order (SCM-03) | `purchase_orders`, `purchase_order_items`, `document_sequences` | `/purchase-orders` | `permission:purchase-orders.*` |
| Auto PO / Reorder (SCM-04) | `products`, `stock_balances`, `purchase_orders` (real-time, tanpa tabel baru) | `/purchase-orders/reorder` | `permission:auto-po.*` |
| Confirm Order / Print (SCM-05) | `purchase_orders`, `purchase_order_items` (tanpa tabel baru) | `/purchase-orders/{purchaseOrder}/print` | `permission:purchase-orders.print` |
| Purchase Receipt (SCM-06) | `purchase_receipts`, `purchase_receipt_items`, `purchase_order_items.qty_received` | `/purchase-receipts` | `permission:purchase-receipts.*` |
| Stock IN Receipt (SCM-07) | `stock_balances`, `stock_movements` (`source_type = po_receipt`) | `/purchase-receipts/{purchaseReceipt}/stock-in` | `permission:purchase-receipts.process-stock-in` |
| Persediaan | `stock_balances`, `stock_movements` | `/inventory`, `/inventory/adjustment` | auth |
| Delivery Order | `delivery_orders`, `delivery_order_items` | `/delivery-orders` | auth |
| Shipment | `shipments` | `/shipments` | auth |
| Invoice | `invoices`, `invoice_items` | `/invoices` | auth |
| Payment | `payments` | `/payments` | auth |
| Laporan | — | `/reports/{stock,sales,receivables,payments}` | auth |
| Import Excel | customer + produk | `/imports` | auth |
| User Management | `users` + role | `/users` | auth (menu hanya admin) |
| AI Assistant | snapshot per role | `POST /ai/chat` | `role:admin\|sales\|finance` + throttle |

Data awal: `DatabaseSeeder` → role (`admin`, `sales`, `finance`), admin user, produk, supplier (EPFM/TOP 30), dummy data.

---

## 5. Alur bisnis

### 5.1 Outbound — penjualan dan penagihan

```mermaid
flowchart LR
    A[Customer] --> B[Delivery Order<br/>validated]
    B -->|reserve stok<br/>qty_reserved +| C[Shipment<br/>shipped]
    C -->|potong stok<br/>qty_available -, qty_reserved -| D[Shipment<br/>received]
    D --> E[Invoice<br/>unpaid]
    E -->|Payment| F[Invoice<br/>paid]
    B -.->|batal| G[cancelled<br/>reserve dilepas]
    C -.->|batal| H[cancelled<br/>stok dikembalikan]
```

Detail perilaku:

- **DO dibuat** → status `validated`, stok **direservasi** (`qty_reserved += qty`) setelah cek `qty_available - qty_reserved` cukup. Harga memakai `products.base_price` dikurangi `product_discounts` yang cocok dengan qty. DO **ditolak** jika produk tidak aktif, `base_price` tidak valid, qty bukan bilangan bulat positif, atau strata diskon tumpang tindih.
- **Shipment dibuat** → status `shipped`, stok **dipotong** (`qty_available -=`, `qty_reserved -=`) dan `stock_movements` tipe `out` dicatat lewat `StockService`. DO menjadi `shipped`.
- **Mark received** → shipment `received`, DO `delivered`. Tidak memotong stok lagi.
- **Invoice** dibuat **manual** dari DO berstatus `shipped`; item disalin, `due_date = invoice_date + payment_term_days`, status `unpaid`.
- **Payment** mengurangi `receivable_amount`; invoice menjadi `paid` bila sisa 0. Pembayaran bisa dihapus dan saldo dihitung ulang.
- Invoice yang sudah ada pembayaran tidak bisa dibatalkan; shipment yang sudah `received` tidak bisa dibatalkan.

### 5.2 Inbound — pengadaan (SCM)

```mermaid
flowchart LR
    A[Supplier Master<br/>active] --> B[Data Purchasing Produk<br/>purchase_price, weight_kg,<br/>dynamics_code, target_stock]
    B --> C[PO draft<br/>snapshot + alokasi mingguan]
    C -->|confirm| D[PO confirmed]
    C -->|cancel| E[PO cancelled]
    D -->|cancel| E
    D -.->|SCM-04| F[qty_on_order / reorder point]
    D -->|SCM-06 receipt| H[PO partial / received]
    H -->|SCM-07 Stock IN| I[stock_balances.qty_available + qty_received<br/>stock_movements type in]
```

Yang **sudah** ada: supplier, data pembelian produk, PO draft/confirm/cancel, nomor PO concurrency-safe, snapshot produk, PPN 11% snapshot, rekomendasi Auto PO (draft), dokumen Confirm Order siap cetak (browser print), Purchase Receipt (partial/full, tracking `qty_received`, nomor receipt concurrency-safe), dan Stock IN eksplisit dari receipt ke inventory existing (`StockService::processPurchaseReceipt()`).

Yang **belum** ada: ekspor PDF, kirim PO via email/WhatsApp.

**PO dan Purchase Receipt tidak menyentuh stok.** Stok hanya bertambah saat action **Process Stock IN** dijalankan pada detail receipt (SCM-07), sekali per receipt.

### 5.3 Kebijakan harga, diskon, dan kuantitas (Plan A)

- **Sumber harga.** `products.base_price` adalah harga jual dasar. Harga transaksi dihitung `final_price = round(base_price × (1 − discount_percentage/100), 2)` dan `line_total = round(qty × final_price, 2)`.
- **Diskon strata.** `product_discounts` dipilih berdasarkan `min_qty ≤ qty ≤ max_qty` (`max_qty` kosong = tanpa batas atas). Pemilihan tidak bergantung urutan database. Jika lebih dari satu strata cocok (overlap/duplikat), transaksi **ditolak** dengan pesan spesifik produk; aplikasi tidak memilih salah satu secara acak.
- **Validasi harga sebelum DO.** `DeliveryOrderService` menolak produk yang tidak ditemukan, `is_active = false`, `base_price ≤ 0`, qty bukan bilangan bulat positif, diskon di luar 0–100, atau `final_price ≤ 0`. Validasi dilakukan sebelum penulisan; kegagalan tidak meninggalkan DO/item/reservasi stok parsial.
- **Invoice.** `InvoiceService` menyalin snapshot DO dan menolak item dengan harga nol, qty ≤ 0, diskon tidak valid, atau `line_total` yang tidak sama dengan `qty × final_price` (toleransi pembulatan Rp0,01).
- **Semantik template invoice.** Kolom `PRICE` pada template adalah harga bersih per unit (`final_price`), `QTY` adalah qty, dan `AMOUNT = PRICE × QTY`. Ekspor menulis `final_price` ke kolom F dan `line_total` ke kolom H, sehingga diskon tidak dihitung dua kali.
- **Kuantitas integer.** Kolom `delivery_order_items.qty` dan `invoice_items.qty` bertipe integer. Request DO, `DeliveryOrderService`, `DailySalesImport`, dan validasi `min_qty`/`max_qty` menolak desimal. Perubahan ke qty desimal memerlukan migration dan keputusan bisnis terpisah.
- **Import produk.** `ProductsImport` hanya menyelaraskan identitas (nama, UOM, segment). Produk baru dibuat `is_active = false` dan `base_price = 0` (default DB) karena workbook `LIST ITEM` asli tidak tersedia dan pemetaan harga belum terbukti. Produk lama tidak diubah `base_price`/`is_active`-nya saat import ulang. Produk baru harus diisi harga dan diaktifkan manual sebelum dapat dipakai transaksi.
- **Import penjualan harian.** `DailySalesImport` memakai `product_discounts` bila ada; jika tidak, memakai kolom `DISCOUNT` workbook (fraksi). Harga `PRICE` workbook diperlakukan sebagai harga bersih. Import menolak qty desimal/nol, harga nol, diskon di luar 0–1, dan `AMOUNT` yang tidak konsisten dengan `PRICE × QTY`.
- **Keterbatasan workbook.** Workbook master `LIST ITEM` tidak tersedia di repo/mesin. Pemetaan kolom harga S1–S4 belum dapat diverifikasi; jangan mengisi `product_discounts` dari workbook tanpa rentang qty yang valid.

---

## 6. Model data

### 6.1 Master

| Tabel | Isi penting |
| --- | --- |
| `users` | name, email, password, `is_active`, role via Spatie |
| `customers` | `customer_code`, `customer_name`, wilayah (province/city/district/sub_district), `address`, `is_active`, soft delete |
| `products` | `product_code`, `product_name`, `base_price` (harga jual), `uom` (`BOX`/`SACK`), `net_weight` (teks), `segment`, `is_active`, soft delete + kolom SCM-02: `dynamics_code`, `purchase_price`, `weight_kg`, `target_stock` |
| `product_discounts` | diskon per `strata_level` (S1–S3 untuk SACK, S1–S5 untuk BOX) dengan rentang `min_qty`/`max_qty` |
| `suppliers` | `code`, `name`, `region`, `port_depo`, `delivery_mode`, `term_of_payment_days`, `pic_name`, `is_active` |

### 6.2 Persediaan

| Tabel | Isi penting |
| --- | --- |
| `stock_balances` | satu baris per produk: `qty_available`, `qty_reserved` |
| `stock_movements` | kartu stok: `movement_date`, `movement_type` (`in`, `out`, `return`, `damage`, `adjustment_in`, `adjustment_out`), `qty`, `source_type` (`shipment`, `manual_adjustment`, `po_receipt`), `source_id`, `notes`, `created_by` |

### 6.3 Penjualan

| Tabel | Isi penting |
| --- | --- |
| `delivery_orders` | `do_number` unik, `customer_id`, `sales_id`, `do_date`, `planned_delivery_date`, `received_date`, `total_amount`, status (`validated`/`shipped`/`delivered`/`cancelled`), soft delete |
| `delivery_order_items` | `qty`, `base_price`, `discount_percentage`, `final_price`, `line_total` |
| `shipments` | `shipment_number`, `delivery_order_id`, tanggal kirim/terima, driver, kendaraan, status (`shipped`/`received`/`cancelled`) |
| `invoices` | `invoice_number` unik, `delivery_order_id` unik, `customer_id`, `invoice_date`, `payment_term_days`, `due_date`, `subtotal`, `grand_total`, `paid_total`, `receivable_amount`, status (`unpaid`/`paid`/`cancelled`), soft delete |
| `invoice_items` | salinan item DO: `qty`, `base_price`, `discount_percentage`, `final_price`, `line_total` |
| `payments` | `payment_number`, `invoice_id`, `payment_date`, `payment_method`, `amount`, `bank_name`, `reference_no`, soft delete |

### 6.4 Pembelian (SCM)

| Tabel | Isi penting |
| --- | --- |
| `document_sequences` | counter nomor dokumen: `document_type`, `period` (`YYYY-MM`), `last_number`; **unik** `(document_type, period)` |
| `purchase_orders` | `po_number` unik, `period`, `supplier_id`, `po_date`, status, `subtotal`, `special_discount_total`, `ppn_rate`, `ppn_amount`, `grand_total`, `created_by`, `notes` |
| `purchase_order_items` | `product_id` + snapshot `dynamics_code`, `weight_kg`, `price`; `qty_plan`, `qty_ton`, `week_1..week_4`, `special_discount`, `price_nett`, `total_price`, `qty_received` |
| `purchase_receipts` | `receipt_number` unik, `purchase_order_id` (`RESTRICT`), `receipt_date`, `status` (default `posted`), `notes`, `created_by` (`SET NULL`) |
| `purchase_receipt_items` | `purchase_receipt_id` (`CASCADE`), `purchase_order_item_id` (`RESTRICT`), `product_id` (`RESTRICT`), `qty_received`, snapshot `weight_kg`, `qty_ton` |

### 6.5 Relasi kunci

```text
Customer 1─n DeliveryOrder 1─n DeliveryOrderItem n─1 Product
DeliveryOrder 1─1 Shipment
DeliveryOrder 1─1 Invoice 1─n InvoiceItem n─1 Product
Invoice 1─n Payment
Product 1─n StockBalance
Product 1─n StockMovement
Supplier 1─n PurchaseOrder 1─n PurchaseOrderItem n─1 Product
PurchaseOrder 1─n PurchaseReceipt 1─n PurchaseReceiptItem n─1 PurchaseOrderItem
User 1─n (DeliveryOrder.sales_id, PurchaseOrder.created_by, PurchaseReceipt.created_by, semua created_by)
```

Delete behavior penting: `delivery_order_id` pada invoice `RESTRICT`, `customer_id` `RESTRICT`, `supplier_id` pada PO `RESTRICT`, item PO `CASCADE` bila PO dihapus, item receipt `CASCADE` bila receipt dihapus, `purchase_order_id` pada receipt `RESTRICT` (receipt = histori), `created_by` `SET NULL`.

---

## 7. Penomoran dokumen

Tiga pola berbeda hidup berdampingan — ini disengaja (warisan vs standar baru):

| Dokumen | Format | Mekanisme | Catatan |
| --- | --- | --- | --- |
| Delivery Order | `DO-20261029HHMMSS-123` | timestamp + random | praktis unik, tidak berurutan |
| Shipment | `SHP-20261029HHMMSS-123` | timestamp + random | idem |
| Payment | `PAY-20261029HHMMSS-123` | timestamp + random | idem |
| Invoice | `932/SI/TTP/X/2026` | `Invoice::booted()` ambil `orderByDesc('id')->first()` lalu +1 | **rawan duplikasi** pada request bersamaan |
| Purchase Order | `001/09/2026` | `document_sequences` + `lockForUpdate()` | concurrency-safe, urut per bulan |
| Purchase Receipt | `001/09/2026` | `document_sequences` (`document_type = purchase_receipt`) + `lockForUpdate()` | periode dari `receipt_date`; nomor bisa sama dengan PO karena tipe dokumen berbeda |

Nomor PO:

```text
{sequence}/{bulan}/{tahun}   →  001/09/2026, 002/09/2026, ...
sequence reset setiap periode (YYYY-MM); Oktober mulai 001/10/2026
```

Mekanisme PO (di dalam transaksi yang sama dengan insert PO):

```php
DB::table('document_sequences')->insertOrIgnore([... 'last_number' => 0]);   // baris ada, bebas race
$row = DB::table('document_sequences')->where(...)->lockForUpdate()->first(); // serialisasi increment
$number = $row->last_number + 1;                                             // UNIQUE(document_type, period) sebagai pengaman akhir
```

Tanpa `MAX()`, tanpa cache, tanpa file counter. Bila insert PO gagal, sequence ikut rollback sehingga nomor dipakai ulang (tidak ada gap). Gap hanya muncul bila `period` draft diubah.

---

## 8. Perhitungan uang

| Area | Cara |
| --- | --- |
| Database | `decimal(15,2)` untuk semua nominal, `decimal(15,4)` untuk qty/ton |
| DO & Invoice | float + `round(..., 2)` per langkah |
| Payment | float + `round(..., 2)` per langkah, `max(0, ...)` pada sisa piutang |
| Purchase Order | **integer sen** (`cents()`), baru dibagi 100 di akhir |

Aturan PO:

```text
price_nett     = price - special_discount          (discount nominal per unit)
total_price    = qty_plan × price_nett
subtotal       = Σ total_price
discount_total = Σ (qty_plan × special_discount)
ppn_amount     = subtotal × ppn_rate / 100         (default 11.00, disimpan per PO)
grand_total    = subtotal + ppn_amount
qty_ton        = qty_plan × weight_kg / 1000       (4 desimal)
```

Harga PO adalah **excl. PPN**. `ppn_rate` disimpan sebagai snapshot agar histori tidak bergantung konfigurasi global.

Alokasi mingguan PO: `W1 = W2 = W3 = floor(qty/4)`, `W4 = sisa`. Jika user mengisi sendiri, total W1–W4 wajib sama dengan `qty_plan`.

Aturan Purchase Receipt:

```text
remaining      = qty_plan - qty_received
valid          = 0 < qty_received_now <= remaining      (divalidasi server)
qty_ton        = qty_received_now × weight_kg / 1000    (snapshot weight_kg PO item, 4 desimal)
status PO      = received bila semua item qty_received == qty_plan, selain itu partial
stock IN       = qty_available += qty_received  (sekali per receipt, qty_reserved tetap)
```

---

## 9. Otorisasi

- Semua route selain `/login` dan `/register` berada di middleware `auth`.
- Middleware `role` dan `permission` berasal dari Spatie Permission (alias di `bootstrap/app.php`).
- Modul Purchasing (SCM-09) memakai permission granular `<modul>.<aksi>`:

  | Permission | Route |
  | --- | --- |
  | `suppliers.view` | `suppliers.index`, `suppliers.show` |
  | `suppliers.create` | `suppliers.create`, `suppliers.store` |
  | `suppliers.update` | `suppliers.edit`, `suppliers.update` |
  | `suppliers.delete` | `suppliers.destroy` |
  | `purchase-orders.view` | `purchase_orders.index`, `purchase_orders.show` |
  | `purchase-orders.create` | `purchase_orders.create`, `purchase_orders.store` |
  | `purchase-orders.update` | `purchase_orders.edit`, `purchase_orders.update` |
  | `purchase-orders.confirm` | `purchase_orders.confirm` |
  | `purchase-orders.cancel` | `purchase_orders.cancel` |
  | `purchase-orders.print` | `purchase_orders.print` |
  | `auto-po.view` | `purchase_orders.reorder` (GET) |
  | `auto-po.create` | `purchase_orders.reorder.generate` (POST) |
  | `purchase-receipts.view` | `purchase_receipts.index`, `purchase_receipts.show` |
  | `purchase-receipts.create` | `purchase_receipts.create`, `purchase_receipts.store` |
  | `purchase-receipts.process-stock-in` | `purchase_receipts.stock_in` |

- Role mapping aktual: `admin` = 15 permission Purchasing (di-seed eksplisit + bypass `Gate::before`); `sales` dan `finance` = 0 permission. Tidak ada role baru.
- Purchase Receipt (SCM-06)/(SCM-07) kini memakai `permission:purchase-receipts.*`; tidak ada lagi `role:admin` di route Purchasing.
- AI Assistant: `role:admin|sales|finance` + `throttle:20,1` (dari `config/ai.php`).
- Sidebar Purchasing memakai `@canany`/`@can`, tombol aksi memakai `@can`; **penyembunyian menu bukan pengaman** — pengaman tetap middleware permission di route (403).
- `Gate::before` membuat user role `admin` lolos semua permission, termasuk modul mendatang.

Keterbatasan yang masih nyata:

- Modul customer, produk, inventory, DO, shipment, invoice, payment, laporan, import, dan user management masih `auth` saja (belum ada pemisahan role per modul).
- Registrasi publik terbuka; user baru langsung login.
- `AuthController::login` tidak mengecek `users.is_active`, sehingga akun nonaktif tetap bisa masuk.
- Permission granular baru diterapkan pada Purchasing; modul lain menyusul (rencana P0b).

---

## 10. AI Assistant (read-only)

- Controller: `AiChatController` (single action) → `AiAssistantService`.
- Konteks dibangun `AiContextBuilder` **per role**: `admin` (agregat seluruh ERP), `sales` (hanya DO miliknya sendiri), `finance` (invoice, piutang, pembayaran).
- Snapshot dikirim sebagai JSON ke provider LLM, instruksi sistem melarang mengarang angka dan melarang aksi tulis.
- Tidak ada tool/function calling. Tidak ada jalur tulis ke database.
- Pengaman tambahan: `DB::listen` menolak SQL selain `select/show/describe/explain/with` selama snapshot dibangun.
- Default **nonaktif**: `AI_ASSISTANT_ENABLED=false`; tanpa `AI_API_KEY` endpoint mengembalikan pesan "belum diaktifkan".

Belum ada: audit log interaksi AI (`AiInteraction`), evaluation suite, agent loop, write action.

---

## 11. Laporan, import, pencarian

- **Laporan**: stok, penjualan, piutang, pembayaran. Masing-masing punya tombol ekspor Excel (`reports/*/export`) memakai PhpSpreadsheet.
- **Import Excel**: satu file master (customer + item) via `TadcoMasterImport` (`maatwebsite/excel`).
- **Pencarian global**: topbar mengetik ≥2 karakter → `/search/global` mengembalikan JSON customer & produk, ditampilkan di dropdown dengan navigasi keyboard.
- **Dashboard**: total customer/produk, stok tersedia & reserved, DO/shipment/invoice, piutang, pembayaran, stok rendah (≤10), transaksi terbaru, dan grafik batang 7 hari (inbound vs outbound) dari `stock_movements`.

---

## 12. Struktur direktori penting

```text
app/
  Http/Controllers/     18 controller + base (thin)
  Http/Requests/        FormRequest (PO, Receipt, Supplier, AI)
  Models/               19 model Eloquent
  Services/             DeliveryOrderService, InvoiceService, PaymentService,
                        PurchaseOrderService, ReorderService, PurchaseReceiptService,
                        StockService
  Services/Ai/          AiAssistantService, AiContextBuilder
  Exports/ Imports/     Excel
database/
  migrations/           31 migration (urut waktu)
  seeders/              role, admin, produk, supplier, dummy
resources/views/
  layouts/app.blade.php layout tunggal
  {customers,products,suppliers,purchase_orders,purchase_receipts,delivery_orders,
   shipments,invoices,payments,inventory,reports,imports,users,auth,dashboard}/
tests/Feature/          13 file, 157 test
routes/web.php          seluruh route web
```

---

## 13. Test dan kualitas

| Perintah | Hasil terakhir |
| --- | --- |
| `php artisan test` | **215 passed**, 1014 assertions, 0 skipped |

Cakupan test yang ada: `PaymentServiceTest`, `InvoiceServiceTest`, `InvoiceModelTest`, `DeliveryOrderServiceTest`, `ProductDiscountTest`, `ProductControllerTest`, `ExcelImportExportTest`, `AiChatEndpointTest`, `SupplierTest`, `ProductPurchasingTest`, `PurchaseOrderTest`, `ReorderTest`, `PurchaseOrderPrintTest`, `PurchaseReceiptTest`, `PurchaseReceiptStockInTest`, `ScmEndToEndTest`, `PurchasingAccessControlTest`. Test baru Plan A mencakup harga nol, produk inactive, qty desimal, overlap strata, rollback DO/invoice, dan konsistensi `final_price × qty = line_total`.

Test memakai SQLite in-memory (`phpunit.xml`) dengan `RefreshDatabase`. **Jangan** mengarahkan test ke database MySQL dev — `RefreshDatabase` akan mengosongkannya.

---

## 14. Batasan dan utang teknis (terverifikasi di kode)

Urusan fungsional:

1. ~~Belum ada penerimaan barang pembelian (purchase receipt)~~ — sudah ada di SCM-06 (`/purchase-receipts`); ~~stock IN dari PO belum~~ — sudah ada di SCM-07 (action **Process Stock IN** per receipt, `stock_movements.source_type = po_receipt`).
2. ~~Belum ada reorder point / auto PO~~ — sudah ada di SCM-04 (`/purchase-orders/reorder`).
3. **Belum ada ekspor PDF** dan pengiriman PO ke supplier (email/WhatsApp); print masih lewat browser (`window.print()`).
4. **Invoice belum bisa dibuat otomatis dari shipment** — masih manual dari DO `shipped`.
5. **Tidak ada audit trail perubahan data ERP** (hanya `stock_movements` dan `created_by`).

Keamanan / konsistensi:

6. Nomor invoice berpotensi duplikat (pola `orderByDesc('id')`); PO sudah benar, invoice belum.
7. Modul ERP lama masih `auth` saja; registrasi publik; `is_active` user diabaikan saat login.
8. Perhitungan uang tidak seragam (float di DO/Invoice/Payment vs integer sen di PO).

Kode yang sudah tidak sinkron:

9. ~~`app/Models/ProductPrice.php` memakai tabel `product_prices` yang **tidak punya migration**~~ — sudah diperbaiki (Plan A, 2026-11-11): model dihapus, harga/diskon memakai `product_discounts`.
10. ~~`app/Exports/StockReportExport.php` masih mereferensikan relasi `warehouse` yang sudah dihapus~~ — sudah diperbaiki (Plan A, 2026-11-11): kolom Gudang dihapus.
11. ~~`app/Imports/ProductsImport.php` menulis kolom `uom_secondary` yang tidak ada~~ — sudah diperbaiki (Plan A, 2026-11-11): import hanya menyelaraskan identitas produk + validasi UOM `BOX`/`SACK`.
12. ~~`ProductController@index` memakai rantai `orWhere` tanpa grouping sehingga filter bisa bocor~~ — sudah diperbaiki (Plan A, 2026-11-11): kondisi pencarian dibungkus closure.

Risiko data dan keputusan bisnis yang masih terbuka:

13. **D9 — `product_discounts` tanpa unique constraint.** Tabel tidak mencegah duplikasi `product_id + strata_level` maupun rentang qty yang tumpang tindih. Aplikasi sekarang mendeteksi overlap/duplikat saat runtime dan menolak transaksi, tetapi belum ada migration/constraint. Keputusan perlu: tambah unique constraint dan/atau validasi database, atau jaga di level aplikasi.
14. **D10 — qty DO/invoice bertipe integer.** Request DO, service, dan import sekarang menolak qty desimal agar tidak ada pemotongan presisi diam-diam. Jika bisnis membutuhkan qty desimal (mis. sebagian sack), diperlukan migration `qty` ke decimal + keputusan pembulatan dan tampilan.
15. **Harga produk hasil import belum terisi.** Workbook `LIST ITEM` asli tidak tersedia, sehingga pemetaan kolom harga S1–S4 ke `base_price`/`product_discounts` belum terbukti. Produk baru hasil import sengaja `is_active = false` dan `base_price = 0`; pemilik data harus mengisi harga dan mengaktifkannya manual. Jangan mengisi harga tebakan.
16. **Konsistensi data historis.** `DailySalesImport` memakai `PRICE` workbook sebagai harga bersih dan menolak baris yang `AMOUNT`-nya tidak sama dengan `PRICE × QTY`. Data historis yang tidak konsisten akan gagal import, bukan dikoreksi otomatis; perlu keputusan bisnis untuk membersihkan/memetakan ulang workbook lama.
17. **Baris import tanpa customer/produk dikenal masih dilewati diam-diam** (`continue`) pada `DailySalesImport`. Perilaku ini pre-existing dan belum diubah; perlu keputusan apakah harus menjadi error atau tetap skip.
18. **Snapshot harga DO → invoice tidak bisa direkonsiliasi dengan master.** Invoice menyalin `final_price`/`line_total` dari DO. Jika master `base_price`/diskon berubah setelah DO dibuat, invoice tetap memakai snapshot DO (memang disengaja), tetapi belum ada laporan audit untuk membandingkannya.

---

## 15. Roadmap

Prioritas proyek (dari `AGENTS.md`):

```text
P0a  Registrasi publik, is_active saat login, role middleware modul sensitif
P0b  Ownership filter (sales_id) + Policy/permission granular
P1   AI Gateway (interface + 2 adapter)
P2   Read-only DB connection untuk AI
P3   AiInteraction audit log
P4   Evaluation / security testing
P5   Optional read-only tools
```

Roadmap SCM:

```text
SCM-01  Supplier Master                    selesai
SCM-02  Product Purchasing Data            selesai
SCM-03  Purchase Order Foundation          selesai
SCM-04  Auto PO / Reorder Point            selesai (rekomendasi + draft)
SCM-05  Print / Confirm Order final        selesai (confirm + dokumen cetak)
SCM-06  Purchase Receipt                   selesai (partial/full, tracking qty, tanpa stok)
SCM-07  Stock IN via StockService          selesai (action eksplisit, idempoten per receipt)
SCM-08  Testing & Hardening SCM            selesai (audit + 8 test tambahan, tanpa perubahan produksi)
SCM-09  Purchasing UI & Access Control     selesai (15 permission granular, sidebar + tombol, 11 test)
```

Rumus reorder yang dipakai SCM-04 (`app/Services/ReorderService.php`):

```text
available_for_reorder = qty_available - qty_reserved
suggested_qty = target_stock - available_for_reorder - qty_on_order
suggested_qty di-clamp ke 0
```

---

## 16. Menjalankan aplikasi

```bash
composer install
cp .env.example .env        # Windows: copy .env.example .env
php artisan key:generate
php artisan migrate --seed  # butuh MySQL aktif (Laragon)
php artisan serve
```

Test:

```bash
php artisan test --testsuite=Feature
```

`php artisan db:seed --class=SupplierSeeder` sudah dijalankan di MySQL dev (`SUP-EPFM` / EPFM, aktif). Isi `target_stock`, `purchase_price`, dan `weight_kg` produk agar produk muncul di `/purchase-orders/reorder`.

---

## 17. Ringkasan satu paragraf

Smart ERP TADCo adalah monolith Laravel server-rendered untuk distribusi barang: master customer/produk/supplier, persediaan berbasis `stock_balances` + kartu stok, alur penjualan DO → Shipment → Invoice → Payment dengan reservasi dan pemotongan stok yang dijaga transaksi, laporan Excel, serta alur pengadaan end-to-end (supplier → data purchasing produk → PO draft/confirm → rekomendasi Auto PO → dokumen Confirm Order siap cetak → Purchase Receipt partial/full → Stock IN idempoten ke inventory existing) dengan penomoran concurrency-safe dan snapshot harga. Lapisan AI bersifat read-only, nonaktif secara default, dan dibatasi per role. Yang belum ada: ekspor PDF PO, pengiriman PO ke supplier, dan audit trail menyeluruh.

---

*Dokumen ini menggambarkan kondisi kode per 5 November 2026 (setelah SCM-09). Jika ada perubahan skema/alur, perbarui file ini bersamaan dengan catatan Obsidian terkait.*
