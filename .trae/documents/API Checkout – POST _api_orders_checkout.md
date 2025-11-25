## Tujuan

* Menambahkan endpoint API untuk menerima data checkout dari frontend, memvalidasi, membuat `orders`, `order_items`, `payment`, `shipment`, menghitung total via `Order::calculateTotals()`, dan mengembalikan respons JSON 200.

## Perubahan Kode

### Route (routes/api.php)

* Tambahkan `POST /api/orders/checkout` ke namespace `App\Http\Controllers\Api\OrderController@store`.

* Terapkan rate limit checkout sesuai Project Rules (mis. `throttle:20,1`).

### Controller (app/Http/Controllers/Api/OrderController.php)

* Buat controller `Api\OrderController` dengan method `store(CheckoutRequest $request): JsonResponse`.

* Gunakan transaksi DB untuk atomicity.

* Alur di `store`:

  1. Ambil `$validated = $request->validated()`.
  2. Buat `Order` berstatus awal `awaiting_payment` dengan `shipping_method` (`pickup|delivery`), set `customer_*`, opsional `courier_name` untuk `delivery`.
  3. Loop `items` → ambil `Variety` dan opsional `SeedLot`, buat `OrderItem::createFromVariety($order, $variety, $quantity, $seedLot)`.
  4. `load('items')` lalu panggil `Order::calculateTotals()`.
  5. Buat `Shipment::createForOrder($order)`.
  6. Buat `Payment::createForOrder($order, $validated['payment_method'] ?? Payment::METHOD_BANK_TRANSFER)` (status pending; finalisasi paid via webhook).
  7. Kembalikan `response()->json([...], 200)` berisi ringkasan order.

* Ketik return type dan patuhi PSR-12.

### Validasi

* Gunakan `CheckoutRequest` yang sudah ada untuk aturan input (telepon, alamat, items, shipping, dsb.).

  * Referensi: `app/Http/Requests/CheckoutRequest.php:22` (rules), `:98` (prepareForValidation), `:115` (withValidator).

### Model & Relasi (reuse yang ada)

* Hitung total dengan `Order::calculateTotals()` → `app/Models/Order.php:344`.

* Buat item dengan snapshot via `OrderItem::createFromVariety()` → `app/Models/OrderItem.php:66`.

* Buat payment via `Payment::createForOrder()` → `app/Models/Payment.php:166`.

* Buat shipment via `Shipment::createForOrder()` → `app/Models/Shipment.php:176`.

* Status order awal: `awaiting_payment` (final paid diset dari webhook yang valid).

### Payload & Respons

* Request body (contoh):

```json
{
  "customer_name": "John Doe",
  "customer_address": "Jl. Contoh No. 1, Jakarta",
  "customer_phone": "+628123456789",
  "customer_email": "john@example.com",
  "shipping_method": "pickup",
  "courier_name": null,
  "items": [
    { "variety_id": 10, "quantity": 2 },
    { "variety_id": 12, "quantity": 1, "seed_lot_id": 5 }
  ],
  "payment_method": "qris",
  "terms_accepted": true
}
```

* Respons 200 (contoh struktur):

```json
{
  "data": {
    "order_code": "WUB-20251125-ABC123",
    "status": "awaiting_payment",
    "shipping_method": "pickup",
    "totals": {
      "subtotal": 150000,
      "shipping_cost": 0,
      "total_amount": 150000
    },
    "payment": {
      "method": "qris",
      "status": "pending",
      "expires_at": "2025-11-26T10:00:00+07:00"
    },
    "shipment": {
      "shipping_method": "pickup",
      "status": "pending"
    }
  }
}
```

* Nilai uang diproses sebagai rupiah (integer di DB; cast decimal:2) dan ditampilkan dalam IDR sesuai konvensi.

## Keamanan & Kepatuhan

* Validasi via `CheckoutRequest`; gunakan transaksi DB dan idempotency sederhana (hindari duplikasi dengan kode order unik).

* Jangan set status `paid` di controller; hanya via webhook dengan verifikasi signature.

* Rate-limit endpoint publik dan catat IP di `Payment` (field tersedia).

* Zona waktu `Asia/Jakarta` untuk timestamp.

## Pengujian & Verifikasi

* Tambah feature test Pest untuk `POST /api/orders/checkout` (status 200, membuat order, items, payment pending, shipment pending, totals benar).

* Jalankan verifikasi endpoint:

```bash
php artisan route:list | findstr /I "api/orders/checkout"
php artisan test --filter=CheckoutApiTest
```

* Uji dengan Postman: kirim body contoh; pastikan respons sesuai; cek DB `orders`, `order_items`, `payments`, `shipments`.

## Integrasi Frontend

* Frontend lokal: `C:\laragon\www\upbs_biogen-client`.

* Pastikan base URL mengarah ke backend (`APP_URL`) dan CORS diizinkan.

* Contoh fetch di frontend (pseudo): kirim POST ke `/api/orders/checkout` dengan body sesuai.

## Langkah CLI (siap tempel)

```bash
php artisan make:controller Api/OrderController
```

## Catatan Asumsi

* Shipping cost untuk `delivery` belum dihitung otomatis (PR-021 akan menambahkan kalkulasi ongkir); untuk saat ini diset 0 atau snapshot manual bila tersedia.

* `payment_method` default ke `bank_transfer` bila tidak dikirim oleh frontend.

* Respons JSON ringkas mengikuti gaya controller API yang sudah ada.

