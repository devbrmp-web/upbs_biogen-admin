# API – Commodities & Varieties Integration (Client ↔ Admin)

Tujuan: Mengaktifkan integrasi data antara upbs_biogen-client (frontend) dan upbs_biogen-admin (backend) melalui API publik yang aman, sesuai SKPL Website UPBS BRMP Biogen.

## Base URL (Lokal)
- Admin API: http://localhost:8000/api
- Client (Frontend): http://localhost:8001

## Endpoint

1) GET /api/commodities
- Deskripsi: Mengambil daftar commodities aktif
- Response (200):
```json
{
  "data": [
    {"id": 1, "name": "Rice", "slug": "rice", "image": "/images/commodities/rice.png"}
  ],
  "meta": {"count": 1}
}
```

2) GET /api/varieties
- Deskripsi: Mengambil daftar varieties aktif, termasuk commodity ringkas
- Query opsional: `?commodity={slug}`
- Response (200):
```json
{
  "data": [
    {
      "id": 10,
      "name": "Inpari 32",
      "slug": "inpari-32",
      "sku": "VR-00010",
      "price_cents": 1250000,
      "price_idr": "Rp 12.500",
      "minimum_limit": 50,
      "image": "/images/varieties/inpari-32.png",
      "commodity": {"name": "Rice", "slug": "rice"}
    }
  ],
  "meta": {"count": 1}
}
```

3) GET /api/varieties/{slug}
- Deskripsi: Mengambil detail variety berdasarkan slug
- Response (200):
```json
{
  "data": {
    "id": 10,
    "name": "Inpari 32",
    "slug": "inpari-32",
    "sku": "VR-00010",
    "description": "",
    "image": "/images/varieties/inpari-32.png",
    "price_cents": 1250000,
    "price_idr": "Rp 12.500",
    "minimum_limit": 50,
    "commodity": {"name": "Rice", "slug": "rice"},
    "stock": {"total_stock_kg": 120.5, "status": "Available"},
    "seed_lots": [
      {"id": 1, "quantity": 50, "unit": "kg", "is_sellable": true, "production_year": 2024}
    ]
  }
}
```

## CORS (Cross-Origin Resource Sharing)
Konfigurasi: `config/cors.php`
```php
'allowed_origins' => [
    'http://localhost:8001',
],
```

Middleware CORS aktif secara global via `\Illuminate\Http\Middleware\HandleCors::class`.

## Client – Cara Mengonsumsi API

### Controller (Laravel – upbs_biogen-client)
```php
use Illuminate\Support\Facades\Http;

class CatalogController extends Controller
{
    public function index()
    {
        $response = Http::get('http://localhost:8000/api/varieties');
        $varieties = $response->json('data') ?? [];
        return view('pages.catalog', ['varieties' => $varieties]);
    }
}
```

### JavaScript (fetch)
```js
async function loadVarieties() {
  const res = await fetch('http://localhost:8000/api/varieties');
  const json = await res.json();
  const items = json.data || [];
  // Render ke elemen daftar pada halaman (tanpa mengubah HTML/CSS existing)
}
```

### JavaScript (axios)
```js
axios.get('http://localhost:8000/api/varieties')
  .then(res => {
    const items = res.data.data || [];
    // Render ke elemen daftar pada halaman
  })
  .catch(err => console.error(err));
```

## Pengujian & Verifikasi

1) Uji CORS
```bash
curl -i -H "Origin: http://localhost:8001" http://localhost:8000/api/varieties
```
Pastikan header `Access-Control-Allow-Origin: http://localhost:8001` muncul.

2) Uji Respons API
```bash
curl http://localhost:8000/api/commodities | jq
curl http://localhost:8000/api/varieties | jq
curl http://localhost:8000/api/varieties/inpari-32 | jq
```

3) Uji Performa
- Target: respons < 150ms di lokal untuk daftar 100 varieties
- Gunakan devtools Network (Chrome/Edge) dan Lighthouse

## Troubleshooting
- CORS blocked:
  - Pastikan `config/cors.php` ada dan `allowed_origins` memuat `http://localhost:8001`
  - Jalankan `php artisan config:clear && php artisan config:cache`
  - Pastikan request dari `http://localhost:8001`
- 404 di `GET /api/varieties/{slug}`:
  - Slug tidak ditemukan; cek data seeders/DB
  - Pastikan route sudah terdaftar di `routes/api.php`
- Performa lambat:
  - Tambah pagination pada endpoint (TODO)
  - Tambah indexing pada kolom yang sering di-query (slug, commodity_id)

## Asumsi
- Harga di database disimpan sebagai integer rupiah (tanpa desimal) untuk kesederhanaan penyimpanan; API tetap menyediakan `price_cents` (rupiah*100) untuk kebutuhan perhitungan presisi sesuai aturan bisnis, dan `price_idr` untuk tampilan (tanpa desimal).
- Endpoint publik hanya GET; write/modify tetap melalui panel admin sesuai SKPL.

## Mapping SKPL
- OUT-015 Katalog (Client) – data diambil via API
- OUT-017 Lihat Keranjang – tidak terpengaruh
- IN-024 Lacak Pesanan – tetap via endpoint tracking terpisah
- PR-028 Audit Log – tidak diaktifkan untuk API publik (read-only)