# UPBS Biogen – Admin (Laravel 11 + Reback)

Admin dashboard untuk proyek **WUB** berbasis **Laravel 11** yang mengintegrasikan tema **Reback** (layout/komponen UI) dengan otentikasi dan middleware peran (role-based access) yang sederhana.

> **Status**: Private repo – internal use only.

## Tech Stack

* **Laravel** 11 (PHP ≥ 8.2)
* **Blade** templating + Reback UI
* **MySQL/MariaDB**
* **Vite** (ESBuild/Rollup) + **npm** sebagai package manager (standar)
* **Paket JS** utama: apexcharts, flatpickr, gridjs, sweetalert2, dsb.

## Quick Start

```bash
# 1) Clone
git clone https://github.com/FatihKawakib04/upbs_biogen-admin.git
cd upbs_biogen-admin

# 2) PHP deps
composer install

# 3) ENV & app key
cp .env.example .env
php artisan key:generate

# 4) Konfigurasi DB di .env, lalu migrasi + seed
php artisan migrate --seed

# 5) JS deps (standarize: npm)
npm ci

# 6) Build assets (production) atau jalankan dev server
npm run build
# atau
npm run dev

# 7) Serve Laravel
php artisan serve
```

> **Windows tips**: gunakan **Git Bash** atau **WSL** saat menjalankan perintah `npm run dev/build` agar environment lebih konsisten.

## Roles & Access

Seeder membuat **dua** role saja (sesuai keputusan terbaru):

* `super_admin` (id: 1) – akses penuh ke seluruh sistem
* `admin` (id: 2) – akses admin operasional terbatas

**Middleware**: `EnsureAdmin`

* Akses rute `admin/*` dibatasi untuk `admin` dan `super_admin`.
* `guest` akan diarahkan ke halaman login.

## Admin Login

### Super Admin

Akun **Super Admin** dibuat otomatis oleh **UserSeeder** dengan kredensial default:

| Field    | Value                    |
|----------|--------------------------|
| Email    | `superadmin@upbs.test`   |
| Password | `password`               |
| Role     | `super_admin` (id: 1)    |

> **Catatan**: Super Admin hanya dibuat sekali jika belum ada user dengan role `super_admin`. Akun ini memiliki akses penuh ke seluruh fitur admin.

### Admin

Akun **Admin** dibuat oleh **AdminUserSeeder** menggunakan variabel `.env`:

```env
ADMIN_NAME="WUB Admin"
ADMIN_EMAIL=admin@upbs.local
ADMIN_PASSWORD=admin123
ADMIN_ROLE_ID=2
```

> Role `admin` (id: 2) memiliki akses operasional terbatas dibandingkan `super_admin`.

### Menjalankan Seeder

Setelah mengubah konfigurasi, **jalankan ulang seeder**:

```bash
php artisan migrate:fresh --seed
```

**Routes penting**

* Halaman login: `GET /login`
* Proses login: `POST /login`
* Dashboard admin: `GET /admin/dashboard`

## Fitur Utama

### 🏠 Dashboard Analytics
- Statistik real-time dengan caching untuk performa optimal
- Visualisasi peta Indonesia untuk distribusi geografis pesanan
- Inventory Watchdog untuk monitoring stok rendah
- Export data ke CSV

### 📦 Manajemen Varietas (Varieties)
- CRUD varietas benih dengan soft deletes
- Galeri gambar dengan drag & drop upload
- Pengelolaan harga melalui SeedLot (bukan di level Variety)
- Price Range dinamis berdasarkan SeedLot

### 🌱 Manajemen Seed Lot
- Tracking lot benih per kelas (BS, FS, Planlet)
- Kolom `harvest_date` untuk tanggal panen
- Harga per unit (`price_per_unit`) di level lot
- Manajemen stok dan kuantitas

### 📋 Manajemen Pesanan (Orders)
- Sistem checkout dengan validasi stok
- Status tracking: pending → confirmed → processing → shipped → delivered/cancelled
- Integrasi pengiriman dengan kalkulasi biaya
- Tanda tangan digital untuk dokumen
- Notifikasi email otomatis

### 👥 Manajemen User Admin
- CRUD user admin
- Role-based access control (super_admin, admin)

### 📊 Audit Log
- Logging otomatis untuk semua operasi CRUD
- Interface admin untuk melihat dan filter audit logs
- Trait `Auditable` untuk model tracking

### 🛒 API Client (Mobile/Web)
- RESTful API untuk commodities, varieties, seed lots, orders
- Endpoint checkout dengan validasi lengkap
- Upload gambar varietas via API

## Scripts yang Tersedia

```bash
# Development (Vite)
npm run dev

# Build production
npm run build

# Lint fix (opsional jika nanti ditambahkan)
npm run lint
```

## Testing

```bash
# Jalankan semua test
php artisan test

# Environment testing
php artisan test --env=testing
```

Contoh pengujian yang tersedia:

* `Tests\Feature\Auth\AdminAuthTest`
* `Tests\Feature\Auth\AdminLoginTest`
* `Tests\Feature\Middleware\AdminMiddlewareTest`
* `Tests\Feature\Dashboard\DashboardTest`
* `Tests\Feature\Order\*` (checkout, management, status)
* `Tests\Feature\ExampleTest`, `Tests\Unit\ExampleTest`

## Struktur Direktori (ringkas)

```
app/
 ├─ Http/
 │   ├─ Controllers/
 │   │   ├─ Admin/          # Dashboard, Order, Variety, SeedLot, Commodity, AuditLog, dll
 │   │   ├─ Api/            # REST API untuk client (mobile/web)
 │   │   ├─ Auth/           # Login, Logout
 │   │   └─ Client/         # Controller untuk client-side
 │   ├─ Middleware/
 │   │   └─ EnsureAdmin.php
 │   └─ Requests/           # Form Requests (CheckoutRequest, dll)
 ├─ Models/
 │   ├─ User.php, Role.php
 │   ├─ Variety.php, VarietyImage.php
 │   ├─ SeedLot.php, SeedClass.php
 │   ├─ Order.php, OrderItem.php
 │   ├─ Commodity.php
 │   ├─ Payment.php, Shipment.php
 │   └─ AuditLog.php
 ├─ Observers/              # Order & Variety observers
 ├─ Traits/
 │   └─ Auditable.php       # Trait untuk audit logging
 └─ Providers/
resources/
 ├─ views/
 │   ├─ layouts/            # Layout Reback
 │   ├─ admin/              # Views untuk dashboard admin
 │   ├─ auth/               # Login, dll
 │   └─ pages/
 └─ js/, scss/
routes/
 ├─ web.php
 ├─ auth.php
 └─ api.php
database/
 ├─ migrations/
 └─ seeders/
     ├─ RoleSeeder.php
     ├─ UserSeeder.php          # Super Admin
     ├─ AdminUserSeeder.php     # Regular Admin
     ├─ CommoditySeeder.php
     ├─ VarietySeeder.php
     ├─ SeedClassSeeder.php
     ├─ SeedLotSeeder.php
     ├─ PlanletSeedLotSeeder.php
     ├─ DemoDataSeeder.php
     └─ DemoOrderSeeder.php
public/
 └─ build/                  # Output Vite
docs/
 └─ middleware-admin.md     # Dokumentasi middleware
```

## Konvensi Commit & Branching (Ringkas)

* **Conventional Commits** disarankan:

  * `feat: ...`, `fix: ...`, `chore: ...`, `docs: ...`, `test: ...`, `refactor: ...`
* Branch:

  * `main` (stabil), feature branch `feat/*`, bugfix `fix/*`

## Keamanan & Dependencies

* Standar **npm**: gunakan `npm ci` (bukan `yarn`/`bun`) untuk konsistensi.
* Audit berkala:

  ```bash
  npm audit
  npm audit fix
  ```
* **Hindari** `npm audit fix --force` di production kecuali paham dampak breaking change (mis. upgrade mayor Vite).
* Sisa advisories dev-only (contoh: `esbuild` dev server) **tidak** berdampak pada runtime produksi setelah `npm run build`.

## Breaking Changes (Branch fatih)

⚠️ **Perubahan penting pada branch ini:**

1. **Harga dipindahkan ke SeedLot**
   - Kolom `price` dihapus dari tabel `varieties`
   - Gunakan `SeedLot::price_per_unit` untuk harga per lot benih
   - Variety sekarang menampilkan "Price Range" yang dihitung dari SeedLot terkait

2. **Checkout wajib menyertakan `seed_lot_id`**
   - Setiap item checkout harus menyertakan `seed_lot_id`
   - Validasi stok menggunakan `SeedLot::quantity`

3. **Soft Deletes**
   - Ditambahkan pada tabel `varieties` dan `variety_images`
   - Data yang dihapus dapat di-restore

## Catatan Penting

* `.env` **jangan** di-commit (sudah di-`.gitignore`).
* Folder `.trae/` **disengaja** ikut di-commit sebagai referensi jejak AI agent.
* Bahasa UI: **Inggris** (konsisten).
* Mohon **tidak mengubah struktur topbar/tema** tanpa diskusi, agar selaras dengan Reback.

## Lisensi

Private / Internal Use Only.
Hak cipta © 2025 UPBS Biogen. Semua hak dilindungi.
