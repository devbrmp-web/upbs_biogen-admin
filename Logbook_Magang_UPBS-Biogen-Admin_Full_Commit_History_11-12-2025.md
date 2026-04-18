### [28-09-2025] : chore: initial import (Laravel 11 + Reback admin)

---

### [29-09-2025] : feat(admin): Categories index (read-only) + Products list/detail pakai DB - Tambah Admin\CategoryController & Admin\ProductController - Model Category & Product + relasi - View admin.categories.index (read-only) - Modif products.blade.php & product-details.blade.php agar bind ke data DB - Tambah menu Categories di left-sidebar - Perkuat middleware EnsureAdmin (403 saat role null) - Tambah migrasi categories & products - Update tests: Middleware\AdminMiddlewareTest pakai route() & seed RoleSeeder

---

### [01-10-2025] : Fix: Tombol Delete pada Categories dan Products Berfungsi dengan Modal Konfirmasi
- Memperbaiki fungsi delete button dengan menambahkan handler JavaScript yang benar.
- Menambahkan fallback mechanism untuk Bootstrap modal konfirmasi.
- Memastikan form DELETE memiliki method DELETE dan CSRF token yang benar pada halaman Categories dan Products.
- Memperbaiki inisialisasi modal dan event listener untuk tombol delete.
- Menambahkan logging console untuk debugging pada JavaScript handler delete.
- Mengonfirmasi bahwa metode destroy() pada CategoryController dan ProductController berfungsi dengan benar, termasuk penghapusan gambar produk.
- Mengonfirmasi konfigurasi Bootstrap dan Vite untuk modal dan skrip yang terkait.
- Melakukan pengujian fungsionalitas delete di kedua halaman (Categories dan Products), memastikan modal konfirmasi muncul dan penghapusan berhasil dilakukan.

Files modified:
- resources/views/admin/categories/index.blade.php
- resources/views/apps/ecommerce/products.blade.php
- resources/js/app.js
- app/Http/Controllers/Admin/CategoryController.php
- app/Http/Controllers/Admin/ProductController.php

---

### [07-10-2025] : Refactor and update commodities and varieties management system:
- Removed unused controllers (CategoryController, ProductController) and models (Category, Product).
- Deleted outdated migration files for categories and products.
- Removed deprecated view files for categories and products (create.blade.php, edit.blade.php, index.blade.php).
- Updated database migrations for commodities and varieties, including the removal of unnecessary columns (description, is_active) from tables.
- Created new controllers for CommodityController, SeedClassController, SeedLotController, and VarietyController to handle new business logic.
- Introduced new models for commodities, seed classes, seed lots, and varieties with updated relationships and attributes.
- Added new migration files to properly structure the commodities, varieties, and seed lot tables.
- Improved pagination UI and layout to align with Reback template, ensuring proper functionality and responsiveness on all devices.
- Updated seeder files for commodities, seed classes, and varieties to match the new database structure.
- Fixed and optimized routing logic in web.php to handle the new endpoints for commodity and variety management.
- Updated tests related to authentication, admin middleware, and pagination to reflect the changes in the system.
- Fixed the alignment and positioning of pagination elements ("Showing X to Y of Z results").
- Optimized database queries to prevent N+1 issues and ensure efficient data retrieval with eager loading where necessary.
- Fixed issues with 'is_active' and 'description' columns removed from models, migrations, and views.
- Fixed bug in pagination links and added proper text positioning for "Showing X to Y of Z results" on both commodities and varieties pages.

---

### [24-10-2025] : feat(varieties): implement stock calculations, add planlet field, update logo and footer branding
- Removed 'Total Stock' input field from create/edit forms; stock now calculated automatically from BS (BS Stock) and FS (FS Stock).
- Added new 'Planlet' input field to create/edit forms for 'per-bottle' units (integer values only).
- Updated stock validation in VarietyController to enforce numeric values for BS/FS with decimal support and ensure proper normalization of null values to 0.
- Modified the 'Variety' model to auto-calculate total stock (BS + FS) and display status (Tersedia/Restock/Habis) based on stock values.
- Updated Blade views ('create', 'edit', 'show', 'index') to display 'Planlet', dynamically calculate total stock, and show stock status badges (Tersedia, Restock, Habis) based on the values.
- Enhanced UI with integer-only validation for stock fields and dynamic stock preview for user inputs in create/edit forms.
- Replaced 'Reback. Crafted by Techzaa' with 'UPBS BRMP Biogen' in footer for branding consistency.
- Updated sidebar logo to display the appropriate logos for dark/light modes using provided logo images.
- Adjusted SCSS variables ('--logo-lg-height', '--logo-sm-height') for responsive and proportional logo display.
- Added new migration 'add_planlet_to_varieties_table.php' to introduce the Planlet field in the database schema.
- Created and updated test cases to ensure correct functionality and validate stock-related features.
- Revised various Blade files, controllers, models, and database migrations as part of the cleanup and feature updates.

---

### [25-10-2025] : [Perbaikan alur update SeedLot] \n- Menambahkan validasi quantity integer-only yang konsisten di StoreSeedLotRequest dan UpdateSeedLotRequest, termasuk pembatasan unit per kelas benih (BS/FS: kg, gram, ton; PL: bottle, piece).\n- Memperbaiki bug: TypeError 'Unsupported operand types: string * int' pada alur pembaruan SeedLot melalui sanitasi rules/messages dan memastikan perhitungan total_value aman secara numerik.\n- Perubahan UI: penyesuaian pesan error/label pada form Seed Lot (Blade: create, edit, index, show) tanpa mengubah struktur tampilan utama.\n- Optimasi kode: perapihan validator, penyelarasan casts di model SeedLot, serta penguatan validasi di public/resources js.\n- Lain-lain: sinkronisasi controller dan views terkait varietas/seed class; seluruh suite test lulus (VarietyManagementTest OK; full suite OK).

---

### [26-10-2025] : fix(admin-users): implement search functionality, clean up tests
- Perbaiki fitur pencarian pada halaman Admin Users agar dapat menggunakan parameter query URL yang konsisten seperti pada halaman Commodities dan Varieties.
- Memperbarui AdminUserController untuk menangani parameter pencarian dan memfilter data berdasarkan nama dan email.
- Memperbarui tampilan halaman Admin Users untuk menggunakan struktur search bar yang seragam.
- Menambahkan pengujian fungsionalitas pencarian dengan parameter query dan memastikan filter bekerja dengan baik.
- Membersihkan file pengujian sementara yang tidak diperlukan.

- Pastikan pencarian bekerja dengan baik, termasuk pencarian case-insensitive dan integrasi dengan paginasi.
- Pembersihan file pengujian yang relevan sudah dilakukan.

---

### [26-10-2025] : fix(admin): standardize seed class terminology and fix pagination - SKPL-WUB-IN-004
- Standardize BS/FS terminology to 'Breeder Seed' and 'Foundation Seed' throughout system
- Fix pagination parameter mismatch in seed lots index (search vs q, variety vs variety_id, etc.)
- Update validation messages, forms, seeders, and tests to use consistent terminology
- Ensure all tests pass with updated terminology

---

### [26-10-2025] : fix(admin/seed-classes): prevent 404 on delete and handle FK gracefully
- Build destroy route with code-based binding in index view (matches getRouteKeyName=code)
- Ensure delete uses proper DELETE form submit (no GET to /seed-classes/{id})
- In controller@destroy: short-circuit when referenced by seed lots; show friendly error flash
- On success, redirect to index with success flash

fix(admin/seed-lots): normalize pagination UI and preserve filters

- Remove leftover diff/placeholder characters around paginator
- Ensure single paginator call using withQueryString()
- Keep filter params (q, variety_id, seed_class_id, is_sellable) consistent with controller
- Minor scoped styling to keep paginator size consistent with other pages

chore(tests): add temp feature tests for delete & pagination during fix, then remove

---

### [28-10-2025] : feat(admin): finalize Seed Lots description unification, UX consistency, and superadmin-first ordering
SUMMARY
- Ready the codebase for Order Management by consolidating data model, fixing filters, and unifying admin UX.

DATABASE / SCHEMA
- Normalize Seed Lots: replace `notes` with `description` (nullable text).
- Remove legacy columns `notes_last_changed_at` and `notes_last_changed_by`.
- Add idempotent normalization migration to safely migrate notes→description and drop legacy columns.

BACKEND
- Enforce integer-only pricing for Varieties: migration to integer rupiah, model cast, controller hardening.
- Seed Lots description: controller + model now consistently use `description` end-to-end.
- Return URL hardening: `sanitizeReturnUrl` helper (admin scope only, strip transient AJAX flags, whitelist filters).
- Admin Users: list ordering ensures superadmins appear first (role_id ASC, then created_at ASC, then id ASC).

FRONTEND / UX
- Progressive enhancement (AJAX) standardized across Seed Lots, Commodities, Varieties, Seed Classes, Admin Users:
  • Debounced search, AJAX pagination, pushState/popstate, clean URLs (no `ajax=1`).
  • Clear Filters button state fixed and consistent.
- Stock filters (Varieties):
  • Available: total sellable kg > minimum_limit.
  • Restock: total sellable kg > 0 and <= minimum_limit.
  • Out of Stock: no sellable kg > 0 (kg-only constraint enforced).
- Seed Lots detail: description renders with `white-space: pre-line`; timestamps standardized (d M Y, H:i).
- Language: runtime UI strings unified to **English**.

TESTS
- VarietyPriceForm tests for integer price validation & display.
- VarietyStockStatusFilter tests (available/restock/out-of-stock semantics).
- SeedLot normalization & validation checks (description ≤ 1000 chars).
- Admin Users ordering & search tests (superadmin-first, AJAX & pagination preserved).

SECURITY & STABILITY
- Prevent open-redirects via sanitized return URLs.
- Controllers return partials only for true XHR requests.

OPS / NOTES
- Tested locally with MySQL (`DB_CONNECTION=mysql`); SQLite used only for test env.
- All suites green (≈190 tests / 700+ assertions in prior runs).

NEXT
- Proceed with Order Management – Stage 1 (cart/checkout, shipping, payments) on top of this clean baseline.

---

### [28-10-2025] : fix error ',' expected on index.blade.php in admin-users, commodities, varieties

---

### [04-11-2025] : Optimized theme switching, enhanced order management, improved error pages, and database restructuring
- Reduced light/dark mode transition duration to 0.1s, implemented cubic-bezier for smoother animations, and added GPU acceleration for better performance.
- Restructured database, migrating from 'categories/products' to 'commodities/varieties' for better alignment with the seed industry model. Removed unnecessary columns.
- Improved order management workflow with status changes from awaiting_payment to completed, CSV export/import, guest order tracking, and email notifications for order status.
- Updated error pages (404, 403, 500, 419) with the UPBS BRMP Biogen logo and text, and added a 'Back to Homepage' button on all error pages for consistent user experience.
- Enhanced admin user management system with restoration of soft-deleted users, and optimized search and pagination.
- Improved security with comprehensive audit logging, rate limiting for public endpoints, and enhanced CSRF protection and input validation.
- Extensive cross-device and cross-browser testing to ensure smooth theme switching and compatibility.
- Cleaned up code by removing unnecessary debug statements and temporary code fragments.

Files modified/added:
- Resources/views/errors/404.blade.php, 403.blade.php, 500.blade.php, 419.blade.php
- Resources/js/theme-improvements.js
- Migrations for commodities and varieties
- Admin controllers and models for order management and admin users
- Testing files: ThemePerformanceOptimizedTest, ErrorPagesBrandingTest

---

### [05-11-2025] : Optimized theme switching, enhanced order management, improved error pages, and database restructuring
- Reduced light/dark mode transition duration to 0.1s, implemented cubic-bezier for smoother animations, and added GPU acceleration for better performance.
- Restructured database, migrating from 'categories/products' to 'commodities/varieties' for better alignment with the seed industry model. Removed unnecessary columns.
- Improved order management workflow with status changes from awaiting_payment to completed, CSV export/import, guest order tracking, and email notifications for order status.
- Updated error pages (404, 403, 500, 419) with the UPBS BRMP Biogen logo and text, and added a 'Back to Homepage' button on all error pages for consistent user experience.
- Enhanced admin user management system with restoration of soft-deleted users, and optimized search and pagination.
- Improved security with comprehensive audit logging, rate limiting for public endpoints, and enhanced CSRF protection and input validation.
- Extensive cross-device and cross-browser testing to ensure smooth theme switching and compatibility.
- Cleaned up code by removing unnecessary debug statements and temporary code fragments.

Files modified/added:
- Resources/views/errors/404.blade.php, 403.blade.php, 500.blade.php, 419.blade.php
- Resources/js/theme-improvements.js
- Migrations for commodities and varieties
- Admin controllers and models for order management and admin users
- Testing files: ThemePerformanceOptimizedTest, ErrorPagesBrandingTest

---

### [05-11-2025] : Implemented API for Commodities and Varieties, added CORS configuration, and rate limiting
- Created CommodityController and VarietyController with endpoints for retrieving commodities and varieties data.
- Implemented CORS configuration to allow access from upbs_biogen-client ({{CLIENT_APP_URL}}).
- Applied rate limiting for public API endpoints (/commodities, /varieties).
- Updated routes/api.php and added necessary middleware for API requests.
- Added documentation for API endpoints in docs/api-commodities-varieties.md.
- Cleaned up debug statements (removed console.log, dd()).

Implemented API for Commodities and Varieties, added CORS configuration, and rate limiting

- Created CommodityController and VarietyController with endpoints for retrieving commodities and varieties data.
- Implemented CORS configuration to allow access from upbs_biogen-client ({{CLIENT_APP_URL}}).
- Applied rate limiting for public API endpoints (/commodities, /varieties).
- Updated routes/api.php and added necessary middleware for API requests.
- Added documentation for API endpoints in docs/api-commodities-varieties.md.
- Cleaned up debug statements (removed console.log, dd()).

~                                                                                                                                                                                            ~                                                                                                                                                                                            ~                                                                                                                                                                                            ~                                                                                                                                                                                            ~                                                                                                                                                                                            ~                                                                                                                                                                                            ~                                                                                                                                                                                            ~                                                                                                                                                                                            ~                                                                                                                                                                                            ~                                                                                                                                                                                            ~                                                                                                                                                                                            ~                                                                                                                                                                                            ~                                                                                                                                                                                            ~                                                                                                                                                                                            ~                                                                                                                                                                                            ~                                                                                                                                                                                            ~                                                                                                                                                                                            ~                                                                                                                                                                                            ~                                                                                                                                                                                            .git/COMMIT_EDITMSG[+] [unix] (00:14 05/11/2025)                                                                                                                                      8,1 All
recording @a                                                                                                                                                                         #

---

### [09-11-2025] : Edit icon sidebar of Orders nav

---

### [25-11-2025] : feat(order): add guest checkout & tracking API – SKPL-WUB-IN-020, IN-024
- Add POST /api/orders/checkout with CheckoutRequest validation
- Implement OrderController@store to create Order, OrderItem, Payment, Shipment
- Add GET /api/orders/track/{tracking_number} with TrackOrderRequest
- Resolve tracking via orders.tracking_number or shipments.tracking_number
- Register routes with throttling in routes/api.php
- Add rate limiter 'api' and 'track' in RouteServiceProvider
- Add feature tests for checkout and tracking endpoints

---

### [26-11-2025] : fix: add middleware at tracking api and add allowed client url

---

### [28-11-2025] : [feat] API checkout: BS/FS & stok seed lot
Menambahkan validasi checkout untuk kelas benih BS/FS, termasuk:
- BS wajib kelipatan 5 kg; FS minimal 1 kg
- Verifikasi seed_lot sellable & stok cukup; fallback ke stok variety
- Sanitasi nomor telepon, default shipping_method=pickup
- Acknowledgement koordinasi untuk metode delivery

Mengimplementasikan alur checkout di API:
- Transaksi DB, pengurangan stok pada seed_lot / variety
- Snapshot harga per item ( unit_price / price_at_order )
- Kalkulasi subtotal dan total_amount , buat shipment dan payment
- Endpoint POST /api/orders/checkout bernama api.orders.checkout (+ rate-limit)
- Endpoint tracking GET /api/orders/track/{tracking_number} bernama api.orders.track

Memperbarui model dan logika bisnis:
- Order : konstanta status, metode transisi, calculateTotals , instruksi pengiriman
- OrderItem : harga BS per kelompok 5 kg, helper createFromVariety , format IDR

API Variety :
- List & detail mengembalikan price_cents dan price_idr
- Sertakan ringkas stok dan seed_lots

Admin Varieties:
- Validasi price sebagai integer, pengelolaan gambar, sanitasi return URL
- View edit menambahkan pembatasan input angka-only

Command artisan:
- verify:order-logic untuk uji harga BS/FS & stok
- db:verify-orders-schema untuk menampilkan skema tabel utama

Migrasi:
- Pembersihan kolom orders : hapus shipping_cost , shipped_at , courier_service
- Perbaiki kolasi courier_name

Testing:
- CheckoutBsFsTest memverifikasi validasi BS, perhitungan total, dan pengurangan stok untuk BS/FS

---

### [29-11-2025] : [feat] tambahkan validasi checkout BS/FS, stok lot, dan API variety
- Memenuhi SKPL-WUB: IN-020 (Checkout), PR-021 (Aturan benih & stok), PR-022 (Pembayaran stub)
- Menjamin akurasi harga: BS per kelompok 5 kg; selain BS (FS/planlet) gunakan harga × kuantitas
- Menjaga integritas stok: kurangi stok pada seed lot/variety secara atomik dalam transaksi
- Menyederhanakan konsumsi frontend: API Variety mengirim price_cents / price_idr dan ringkasan stok
- Memperketat input admin: harga disimpan sebagai integer rupiah; pembatasan angka-only
- Meningkatkan reliabilitas: tambah command verifikasi logika dan test fitur BS/FS
- Dampak: kolom shipping_cost dihapus; referensi di logika/response perlu disesuaikan agar konsisten
- Menyiapkan dasar integrasi webhook pembayaran dan ongkir di fase berikutnya

---

### [29-11-2025] : [feat] tambahkan validasi checkout BS/FS & stok lot
- Mengapa: menerapkan aturan bisnis BS/FS dan menjaga integritas stok untuk akurasi transaksi checkout tamu
- Dampak teknis/bisnis: transaksi atomik, snapshot harga per item, endpoint checkout/tracking ber-rate-limit, API variety lebih konsisten (price_cents/price_idr), form admin harga integer
- Referensi SKPL: IN-020 (Checkout), PR-021 (Aturan benih & stok), PR-022 (Pembayaran – dasar)
- Validasi BS wajib kelipatan 5 kg; selain BS (FS/planlet) gunakan harga × kuantitas
- Verifikasi seed_lot sellable dan stok; fallback ke stok variety bila tanpa lot
- Kurangi stok seed_lot/variety secara atomik dalam transaksi checkout
- Snapshot harga ke order_items (unit_price, price_at_order); total BS dihitung per kelompok 5 kg
- Hitung subtotal dan total_amount dari seluruh item; siapkan shipment dan payment
- Tambah endpoint checkout dan tracking bernama dengan rate-limit
- API Variety kirim price_cents / price_idr dan ringkasan stok serta seed lots
- Admin Varieties: validasi harga integer, sanitasi return URL, pembatasan input angka-only
- Command verifikasi: uji logika BS/FS dan tampilkan skema tabel terkait
- Test: skenario BS/FS untuk validasi, total harga, dan pengurangan stok
- Catatan dampak: kolom shipping_cost dihapus pada skema; sesuaikan referensi agar konsisten di kode dan respons API

---

### [30-11-2025] : [feat] tambahkan validasi checkout BS/FS, stok lot, dan API variety
- Mengapa: menerapkan aturan bisnis BS/FS dan menjaga integritas stok serta akurasi harga pada checkout tamu
- Dampak teknis/bisnis: transaksi atomik untuk pengurangan stok, snapshot harga item, endpoint checkout/tracking ber-rate-limit, API harga konsisten (price_cents/price_idr), form admin memastikan harga integer
- Referensi SKPL-WUB: IN-020 (Checkout), PR-021 (Aturan benih & stok), PR-022 (Pembayaran – dasar)
- Validasi BS wajib kelipatan 5 kg; selain BS (FS/planlet) harga dihitung unit × kuantitas
- Verifikasi seed_lot sellable dan stok; fallback ke stok variety ketika tanpa lot
- Kurangi stok seed_lot/variety secara atomik dalam transaksi checkout
- Snapshot harga ke order_items (unit_price, price_at_order); total BS dihitung per kelompok 5 kg
- Hitung subtotal dan total_amount dari seluruh item; siapkan shipment dan payment
- Tambah endpoint checkout dan tracking bernama dengan rate-limit
- API Variety kirim price_cents / price_idr dan ringkasan stok serta seed_lots
- Admin Varieties: validasi harga integer, sanitasi return URL, pembatasan input angka-only
- Command verifikasi: uji logika BS/FS dan tampilkan skema tabel terkait
- Test: skenario BS/FS untuk validasi, total harga, dan pengurangan stok
- Catatan dampak: kolom shipping_cost dihapus pada skema; sesuaikan referensi agar konsisten di kode dan respons API

---

### [02-12-2025] : api variety nambahin seed clases supaya tampil

---

### [03-12-2025] : chore: ignore .DS_Store files in git
Add .DS_Store to .gitignore to exclude macOS system files from version control

---

### [03-12-2025] : feat(api): add seed classes and lots endpoints with catalog UI
- Implement API endpoints for seed classes and lots with filtering capabilities
- Add catalog and cart views with basic functionality
- Include request validation, controllers, tests and documentation
- Set up frontend components to display seed classes and lots data

---

### [03-12-2025] : feat(variety): add bySeedClass endpoint and extend seed lot details
Add new API endpoint to get varieties by seed class ID with sellable seed lots
Include lot_code and price_per_unit in seed lot details for variety show endpoint

---

### [03-12-2025] : feat(payments): integrate midtrans payment gateway
- add midtrans configuration file
- create migrations for midtrans payment columns
- implement midtrans service with snap token generation
- add webhook handler for payment notifications
- update order controller to use midtrans service
- add tests for midtrans integration
- update env

---

### [04-12-2025] : feat(orders): enhance order tracking with multiple query options
- Add new API endpoints for tracking orders by query params and order code
- Update TrackOrderRequest to support tracking by order code or phone
- Improve order controller to handle multiple tracking methods
- Refactor response payload structure for consistency

---

### [05-12-2025] : feat(payment): integrate midtrans payment gateway
- Add midtrans/midtrans-php dependency
- Remove old payment config files and consolidate into services.php
- Refactor OrderController to use Midtrans Snap API directly
- Simplify CheckoutRequest validation rules
- Generate snap token for payment processing

---

### [07-12-2025] : fix(orders): resolve z-index stacking issues in modals and dropdowns
Add z-index values to modals and dropdown elements to prevent stacking issues
Move modals to document body when opened to ensure proper display
Fix dropdown visibility in table cells by adjusting z-index dynamically

---

### [10-12-2025] : feat(audit): implement comprehensive audit logging system
- Add Auditable trait to all major models for automatic CRUD logging
- Rename audit log columns to be more database-agnostic (model_type→table_name)
- Create admin interface for viewing audit logs with filtering capabilities
- Add audit log seeder for testing and demo purposes
- Implement automatic logging for model events (create/update/delete)
- Add extensive test coverage for audit logging functionality

---
