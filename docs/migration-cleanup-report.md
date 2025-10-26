# Migration Cleanup Report - Categories/Products to Commodities/Varieties

## Overview
Laporan ini mendokumentasikan perubahan besar pada struktur database aplikasi UPBS BRMP Biogen, dimana konsep "categories" dan "products" telah digantikan dengan "commodities" dan "varieties" sesuai dengan domain bisnis benih pertanian.

## Changes Made

### 1. Deleted Obsolete Migration Files
File-file migrasi berikut telah dihapus karena sudah tidak relevan dan digantikan dengan struktur baru:

- `2025_09_29_120957_create_categories_table.php` → Digantikan dengan `create_commodities_table.php`
- `2025_09_29_121045_create_products_table.php` → Digantikan dengan `create_varieties_table.php`
- `2025_09_30_100001_add_image_path_if_missing_to_categories_table.php` → Sudah terintegrasi dalam tabel commodities
- `2025_09_30_100002_add_missing_columns_to_products_table.php` → Sudah terintegrasi dalam tabel varieties
- `2025_09_30_100003_create_product_ns_batches_table.php` → Digantikan dengan seed_lots system
- `2025_09_30_110000_drop_description_from_categories_table_if_exists.php` → Tidak diperlukan lagi
- `2025_10_01_000001_add_minimum_limit_to_products.php` → Sudah terintegrasi dalam tabel varieties
- `2025_10_07_010646_rename_categories_to_commodities_table.php` → Digantikan dengan create baru
- `2025_10_07_010655_fix_varieties_table_structure.php` → Digantikan dengan create baru

### 2. New Migration Files Created

#### a. `2025_10_07_010645_create_commodities_table.php`
Membuat tabel `commodities` dengan struktur:
- `id` (Primary Key)
- `name` (Nama komoditas: Padi, Jagung, Kedelai, dll)
- `slug` (URL-friendly name, unique)
- `description` (Deskripsi komoditas, nullable)
- `image_url` (URL gambar komoditas, nullable)
- `is_active` (Status aktif, default: true)
- `timestamps`
- Index: `[is_active, name]`

#### b. `2025_10_07_010650_create_varieties_table.php`
Membuat tabel `varieties` dengan struktur:
- `id` (Primary Key)
- `commodity_id` (Foreign Key ke commodities, cascade delete)
- `name` (Nama varietas)
- `slug` (URL-friendly name, unique)
- `sku` (Stock Keeping Unit, unique)
- `description` (Deskripsi varietas, nullable)
- `price` (Harga dasar per unit, decimal 12,2)
- `stock` (Stok total, integer)
- `stock_bs_kg` (Stok BS dalam kg, decimal 12,3)
- `stock_fs_kg` (Stok FS dalam kg, decimal 12,3)
- `minimum_limit` (Batas minimum stok, integer)
- `status` (Enum: available, out_of_stock, discontinued)
- `is_active` (Status aktif, default: true)
- `image` (Path gambar varietas, nullable)
- `timestamps`
- Index: `[commodity_id, is_active]`, `[status, is_active]`, `[sku]`

### 3. Existing Migration Files Retained

#### User & Role Management (Tetap dipertahankan):
- `0001_01_01_000000_create_users_table.php`
- `0001_01_01_000001_create_cache_table.php`
- `0001_01_01_000002_create_jobs_table.php`
- `2025_09_22_084350_add_password_hash_and_role_id_to_users_table.php`
- `2025_09_22_084626_create_roles_table.php`
- `2025_09_23_084553_create_sessions_table.php`
- `2025_09_23_094118_drop_password_column_from_users_table_if_exists.php`
- `2025_09_23_101413_drop_email_verified_at_and_password_from_users.php`
- `2025_09_23_120038_add_fk_users_role_id_to_roles.php`
- `2025_09_23_130209_add_role_id_foreign_key_to_users_table.php`

#### Seed Management (Tetap dipertahankan):
- `2025_10_07_010706_create_seed_classes_table.php`
- `2025_10_07_010716_create_seed_lots_table.php`

## Database Structure Overview

### Relasi Tabel Utama:
```
commodities (1) ←→ (many) varieties
varieties (1) ←→ (many) seed_lots
seed_classes (1) ←→ (many) seed_lots
```

### Seed Classes:
- BS (Breeder Seed)
- FS (Foundation Seed) 
- NS (Benih Sumber)

### Seed Lots:
Mengelola batch/lot benih dengan informasi:
- Kode lot unik
- Tahun produksi
- Kuantitas dan unit
- Harga per unit
- Status dapat dijual

## Migration Test Results

✅ **Migration Status: SUCCESS**

Semua migrasi berhasil dijalankan dengan `php artisan migrate:fresh`:
- 14 migrasi berhasil dieksekusi
- Tidak ada error atau konflik
- Database seeder berhasil dijalankan
- Struktur tabel sesuai dengan rancangan

## Next Steps

1. **Update Models**: Pastikan model Eloquent (Commodity, Variety, SeedClass, SeedLot) sesuai dengan struktur tabel baru
2. **Update Controllers**: Sesuaikan controller untuk menggunakan relasi commodities/varieties
3. **Update Views**: Perbarui tampilan admin panel untuk menggunakan terminologi baru
4. **Update Tests**: Perbaiki unit test dan feature test yang masih menggunakan categories/products
5. **Update Seeders**: Buat seeder untuk commodities dan varieties dengan data realistis

## Files Modified/Created

### Created:
- `database/migrations/2025_10_07_010645_create_commodities_table.php`
- `database/migrations/2025_10_07_010650_create_varieties_table.php`
- `docs/migration-cleanup-report.md`

### Deleted:
- 9 file migrasi obsolete (lihat daftar di atas)

---
*Report generated on: 2025-01-27*
*Migration cleanup completed successfully*