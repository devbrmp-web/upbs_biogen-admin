# Perubahan: Hapus kolom `description` dari `commodities`

Ringkasan
- Kolom `description` pada tabel `commodities` dihapus untuk menyederhanakan model data dan menghindari inkonsistensi dengan UI/validasi yang tidak menggunakan kolom tersebut.
- Seeder dan pengujian diperbarui agar tidak lagi mereferensikan `description` pada `Commodity`.
- Pencarian komoditas di indeks tetap berdasarkan `name` (parameter `q` atau `search`).

File yang Diubah
- `database/migrations/2025_10_09_172853_drop_description_from_commodities_table.php` — migrasi hapus kolom.
- `app/Models/Commodity.php` — hapus `description` dari `$fillable`.
- `database/seeders/CommoditySeeder.php` — hilangkan penggunaan `description` dan konsisten dengan `image_path`.
- `tests/Feature/CommodityTest.php` — hapus referensi `description`; tambah tes pencarian indeks.

Langkah Uji
1) Migrasi & Seed
   - Jalankan: `php artisan migrate:fresh --seed`
   - Pastikan seed berhasil tanpa error.

2) Jalankan Tes Fitur
   - Perintah: `php artisan test --testsuite=Feature --stop-on-failure`
   - Ekspektasi: seluruh tes lulus.

3) Verifikasi UI (Manual)
   - Start server: `php artisan serve --host=127.0.0.1 --port=8000`
   - Buka: `http://127.0.0.1:8000/`
   - Login admin (seed):
     - `admin@upbs.local` / password dari `.env` (AdminUserSeeder)
     - atau `superadmin@upbs.test` / `password`
   - Cek halaman komoditas di panel Admin: buat/edit komoditas tanpa kolom `description` muncul.

Catatan Teknis
- Migrasi `up()` menghapus kolom `description` jika ada; `down()` menambahkan kembali sebagai `text nullable`.
- Source of truth untuk status pembayaran tetap via webhook (tidak terpengaruh perubahan ini).
- Kolom terkait harga, stok, dan relasi tidak berubah.

Env & Dependensi
- `APP_TIMEZONE=Asia/Jakarta`, `APP_LOCALE=id` (opsional)
- `doctrine/dbal` tersedia di `require-dev` (untuk migrasi perubahan kolom bila diperlukan).

Commit Message (Conventional Commits)
- `feat(commodity): drop description column and align tests – SKPL-WUB-PRO-DB-001`

Rollback
- Jika perlu mengembalikan kolom:
  - Jalankan: `php artisan migrate:rollback` hingga migrasi penghapusan dibatalkan.