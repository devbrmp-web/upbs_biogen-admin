# API Seed Classes & Seed Lots

Endpoint publik untuk konsumsi frontend client (guest). Semua respons menggunakan JSON dan berada di bawah prefix `/api` dengan rate-limit bawaan.

## Seed Classes

- `GET /api/seed-classes`
  - Query:
    - `q` (string, optional): pencarian berdasarkan `name`, `code`, `description`.
    - `active_only` (boolean, default: true): jika `true` hanya kelas aktif.
  - Response:
    - `data[]`: `{ id, code, name, description, is_active }`
    - `meta.count`: jumlah item.

- `GET /api/seed-classes/{code}`
  - Path parameter: `code` (contoh: `BS`, `FS`, `PL`, `CS`)
  - Response:
    - `data`: detail kelas.

## Seed Lots

- `GET /api/seed-lots`
  - Query:
    - `sellable_only` (boolean, default: true)
    - `variety_id` (int)
    - `variety_slug` (string)
    - `seed_class_id` (int)
    - `seed_class_code` (string, contoh: `FS`)
    - `production_year` (int)
    - `min_stock` (int, default: 1)
  - Response:
    - `data[]`: `{ id, lot_code, quantity, unit, price_per_unit_cents, price_idr, is_sellable, production_year, variety{ id,name,slug }, seed_class{ id,code,name }, description }`
    - `meta.count`: jumlah item.

- `GET /api/seed-lots/{lot_code}`
  - Path parameter: `lot_code` (contoh: `LOT-AB12`)
  - Response:
    - `data`: detail lot termasuk nested `variety` dan `seed_class`.

## Catatan Implementasi

- Format harga: integer rupiah dikonversi menjadi `price_per_unit_cents` dan juga `price_idr` untuk tampilan.
- Default filter mencegah menampilkan lot non-sellable dan stok kosong.
- Rate-limit: `throttle:20,1` pada semua endpoint publik.
- Validasi parameter dilakukan menggunakan `FormRequest` khusus.

