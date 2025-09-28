# Dokumentasi Middleware Admin

## Deskripsi
Middleware `EnsureAdmin` digunakan untuk membatasi akses ke area admin hanya untuk pengguna dengan role admin atau super_admin.

## Implementasi
Middleware ini sudah diimplementasikan di:
- File: `app/Http/Middleware/EnsureAdmin.php`
- Terdaftar di Kernel.php dengan alias: `ensure.admin`

## Cara Penggunaan
Middleware ini dapat digunakan dengan cara:

1. Menggunakan alias di route:
```php
Route::middleware('ensure.admin')->group(function () {
    // Route yang dilindungi
});
```

2. Menggunakan class di route:
```php
Route::middleware(\App\Http\Middleware\EnsureAdmin::class)->group(function () {
    // Route yang dilindungi
});
```

3. Sudah diterapkan pada grup route admin:
```php
Route::middleware(['web', 'auth', \App\Http\Middleware\EnsureAdmin::class])
    ->prefix('admin')->name('admin.')
    ->group(function () {
        // Route admin
    });
```

## Alur Kerja
1. Jika pengguna belum login, akan diarahkan ke halaman login
2. Jika pengguna sudah login tapi bukan admin/super_admin, akan mendapat error 403
3. Jika pengguna adalah admin/super_admin, request akan dilanjutkan

## Testing
Test untuk middleware ini tersedia di:
- File: `tests/Feature/Middleware/AdminMiddlewareTest.php`
- Mencakup test untuk:
  - Non-admin mendapat 403
  - Admin dapat mengakses dashboard
  - Super admin dapat mengakses dashboard
  - Guest diarahkan ke login

## Keterkaitan dengan SKPL
Middleware ini mendukung implementasi fitur-fitur admin sesuai SKPL-WUB:
- IN-001 Login Admin
- OUT-002 Logout
- OUT-003 BI Dashboard
- Dan semua fitur admin lainnya