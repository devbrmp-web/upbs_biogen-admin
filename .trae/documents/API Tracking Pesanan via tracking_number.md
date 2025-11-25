## Rute
- Tambah endpoint publik `GET /api/orders/track/{tracking_number}` di `routes/api.php` dengan nama `api.orders.track`.
- Terapkan middleware rate-limit khusus (mis. `throttle:track`) sesuai ketentuan SKPL untuk endpoint publik.

## Controller
- Buat `App\Http\Controllers\Api\OrderController` dengan method `track(string $trackingNumber): \Illuminate\Http\JsonResponse`.
- Eager-load relasi penting (`shipment`, `payment`, `orderItems`) untuk respons komprehensif tanpa N+1.

## Validasi
- Buat `App\Http\Requests\Api\TrackOrderRequest` agar validasi terpisah dan reusable.
- Aturan: `required|string|max:64|regex:/^[A-Za-z0-9._-]+$/`.
- Jika format tidak valid → 422 JSON dengan detail error.

## Pencarian Data
- Cari **orders** terlebih dahulu: `Order::with(['shipment','payment','orderItems'])->where('tracking_number', $trackingNumber)->first()`.
- Jika tidak ditemukan, fallback ke **shipments**: `Shipment::with('order')->where('tracking_number',$trackingNumber)->first()` lalu ambil `order` terkait.
- Jika tetap tidak ditemukan → 404 JSON: `{ "message": "Tracking number not found" }`.

## Respons JSON
- Struktur respons ringkas, aman, dan cukup untuk UI tracking:
```json
{
  "tracking_number": "ID1234567890",
  "order_code": "ORD-2025-0001",
  "status": "shipped",
  "shipment_status": "delivered",
  "courier": { "name": "Pos Indonesia", "service": "Reguler" },
  "timestamps": { "shipped_at": "2025-11-01T10:00:00+07:00", "delivered_at": "2025-11-03T15:30:00+07:00" }
}
```
- `status` diambil dari `orders.status`; `shipment_status` dari `shipments.status` bila ada; tanggal dalam zona waktu `Asia/Jakarta`.

## Rate-Limit & Keamanan
- Definisikan limiter `track` (mis. 30/min per IP) menggunakan RateLimiter.
- Endpoint publik, tidak perlu auth; tetap hindari bocor data sensitif (tanpa alamat lengkap/HP).
- Logging minimal (info) untuk audit, pseudonimkan bila perlu sesuai aturan proyek.

## Testing (Pest)
- Feature tests:
  - `it_returns_200_with_valid_tracking_number` → buat order+shipment factory, hit endpoint, asserta bidang JSON.
  - `it_returns_404_when_tracking_not_found`.
  - `it_returns_422_on_invalid_tracking_format`.
- Gunakan transaksi DB di test dan factories yang ada.

## Verifikasi & CLI
- Daftar rute:
```
php artisan route:list | findstr orders/track
```
- Jalankan server dev:
```
php artisan serve
```
- Uji dengan Postman: `GET http://localhost:8000/api/orders/track/ID1234567890`.
- Jalankan test:
```
php artisan test --filter=OrderTracking
```

## Integrasi Frontend (Client)
- Frontend (`C:\laragon\www\upbs_biogen-client`) melakukan request `GET /api/orders/track/{tracking_number}`.
- Pastikan base URL sudah mengarah ke host admin (Laragon domain atau `localhost:8000`).
- Verifikasi manual melalui Postman dahulu, lalu pastikan UI menampilkan status dari bidang `status/shipment_status`.

## Asumsi
- Asumsi: `tracking_number` tersedia di `orders` dan `shipments`; API memprioritaskan pencarian di `orders` untuk patuh instruksi, lalu fallback ke `shipments` agar kompatibel dengan data yang sudah ada (sesuai temuan kode dan SKPL-WUB).
- Tidak mengubah linter/formatter; tetap PSR-12 dan return type eksplisit.

Jika disetujui, saya akan implementasi rute, controller, request, limiter, dan tests sesuai rencana di atas.