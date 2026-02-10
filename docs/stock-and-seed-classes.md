# Dokumen: Perhitungan Stok (kg-only) & Cleanup Kelas Benih

Project: Website UPBS BRMP Biogen (WUB)
Zona waktu: Asia/Jakarta | Bahasa: Indonesia | Mata uang: IDR

## Ringkasan Perubahan
- Perhitungan stok varietas kini hanya menjumlahkan lot benih dengan `unit = 'kg'` dan `is_sellable = true`.
- Kelas benih `NS` dihapus dari seeder dan dibersihkan via migrasi cleanup; hanya `BS`, `FS`, dan `PL (Planlet)` yang aktif.
- Planlet (`PL`) tetap dicatat via `seed_lots` dengan unit non-berat (`bottle`, `piece`) dan tidak masuk agregasi stok kg.
- Navigasi dari detail Seed Class ke form tambah Seed Lot membawa `seed_class_id` dan otomatis terpilih di form.

## Alur Perhitungan Stok
- Sumber data: tabel `seed_lots` (snapshot harga & unit per lot).
- Filter: `variety_id = ?` AND `is_sellable = true` AND `unit = 'kg'`.
- Agregasi: `SUM(quantity)` sebagai `total_stock`, dan `CASE WHEN seed_class_id = ?` untuk `bs`/`fs`.
- Status stok (`stock_status`):
  - `Habis` bila `total_stock` = 0.
  - `Restock` bila `total_stock` <= `minimum_limit`.
  - `Tersedia` bila `total_stock` > `minimum_limit`.

## File yang Diubah/Ditambah
- Model: `app/Models/Variety.php` — filter `unit = 'kg'` pada accessor dan scope agregasi.
- Controller: `app/Http/Controllers/Admin/VarietyController.php` — agregasi stok di `index`/`show` hanya `kg`.
- Seeder: `database/seeders/SeedClassSeeder.php` — hanya `BS`, `FS`, `PL`.
- Migrasi cleanup: `database/migrations/2025_10_09_110000_cleanup_remove_ns_seed_class.php` — hapus kelas `NS` dan rollback menambahkan kembali sebagai `is_active = false`.
- Test: `tests/Unit/VarietyStockCalculationTest.php` — tambah kasus memastikan lot non-kg diabaikan.

## Cara Uji (Windows / Laragon)
1. Jalankan migrasi ulang dan seeder:
   - `php artisan migrate:fresh --seed`
2. Jalankan test unit terkait stok:
   - `php artisan test --filter=VarietyStockCalculationTest --stop-on-failure`
3. Jalankan server untuk pratinjau UI:
   - `php artisan serve` lalu buka `http://127.0.0.1:8000/`.

## Env & Prasyarat
- `APP_ENV`, `APP_KEY`, koneksi `DB_*` telah dikonfigurasi di `.env`.
- Cache: gunakan `CACHE_DRIVER=file` saat pengembangan; test melakukan flush sesuai kebutuhan.

## Dampak Data & Rollback
- Migrasi cleanup menghapus `seed_classes` dengan `code='NS'`. Karena FK `seed_lots.seed_class_id` ber-`cascadeOnDelete`, lot terkait akan ikut terhapus.
- Rollback (`php artisan migrate:rollback`) akan menambahkan kembali kelas `NS` sebagai tidak aktif tanpa lot.

## Catatan SKPL & Keamanan
- Sesuai SKPL: stok ditampilkan dalam kilogram untuk benih BS/FS; planlet tidak dijumlahkan dalam stok kg.
- Validasi ketat unit per kelas: BS/FS menerima unit berbasis berat; PL menerima `bottle/piece`.
- Jangan percaya status pembayaran dari client; tidak terkait perubahan ini, namun prinsip tetap berlaku.

## Saran Commit Message (Conventional Commits)
- `feat(stock): aggregate kg-only for variety totals – SKPL-WUB-PRO-014`
- `chore(seeder): remove NS seed class and add cleanup migration – SKPL-WUB-PRO-015`
