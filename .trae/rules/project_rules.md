# Project Rules – Website UPBS BRMP Biogen
1) Ruang Lingkup & Prinsip
- Kanal: retail e-commerce benih; BAST di luar sistem.
- Guest checkout (tanpa akun klien). Admin saja yang punya akun.
- Pembayaran PNBP only via payment gateway; status final via webhook.
- Rujukan kebenaran: SKPL-WUB (IDs IN/OUT/PR di Tabel 4). Bila terjadi konflik dengan percakapan, ikuti SKPL (SKPL Website UPBS BRMP Biogen).
2) Stack & Standard
- Implementasi: Laravel 11.x (template Reback), PHP 8.2+, Blade, opsi Tailwind.
    Catatan: SKPL mencantumkan referensi Laravel 12; target implementasi 11.x agar selaras template, siapkan kompatibilitas forward (tanpa fitur khusus 12). 
    SKPL Website UPBS BRMP Biogen
- Testing: Pest (feature + unit), HTTP tests untuk checkout, payment webhook, tracking.
- Kode: PSR-12; Form Request untuk validasi; Service/Action untuk integrasi eksternal.
- Zona waktu default: Asia/Jakarta; format uang IDR (number_format), hindari float—gunakan integer (sen) di level perhitungan.
3) ERD & Model Minimum
- Gunakan tabel berikut (nama boleh singular/plural konsisten proyek):
- users (admin saja), roles,
- products, categories,
- orders, order_items,
- payments, shipments,
- audit_logs.
    Kunci & relasi mengikuti ERD yang sudah disepakati (orders 1–* order_items; order 1–1 payment; order 1–0..1 shipment; user 1–* audit_logs, dst.). 
    SKPL Website UPBS BRMP Biogen
4) Modul & ID Fungsional (harus ada)
Ikuti penamaan dan alur sesuai Tabel 4:
Admin
- IN-001 Login, OUT-002 Logout, OUT-003 BI Dashboard.
- IN-004 Tambah Produk, OUT-005 Lihat Produk, PR-006 Ubah Produk, PR-007 Hapus Produk.
- IN-008 Tambah Kategori, OUT-009 Lihat Kategori, PR-010 Ubah Kategori, PR-011 Hapus Kategori.
- OUT-012 Lihat Pesanan, PR-013 Ubah Status, PR-014 Hapus Pesanan.
- OUT-025 Dokumen Transaksi (PDF), OUT-027 Ekspor CSV, PR-028 Audit Log.
Client (Guest)
- OUT-015 Katalog, IN-016 Tambah ke Keranjang, OUT-017 Lihat Keranjang,
- IN-018 Ubah Item, IN-019 Hapus Item, IN-020 Checkout.
- PR-021 Hitung Ongkir (API), PR-022 Pembayaran (gateway), PR-023 Shipping & Fulfillment (oleh admin).
- IN-024 Lacak Pesanan (sesuai SKPL saat ini).
- OUT-026 Notifikasi Email (oleh sistem), OUT-029 Help & FAQ (SKPL Website UPBS BRMP Biogen).
    Catatan tracking: SKPL menandai IN-024 (R). Walau secara arsitektur “read”, ikuti penomoran SKPL untuk konsistensi dokumen (SKPL Website UPBS BRMP Biogen).
5) Antarmuka Eksternal
- Payment Gateway (mis. Midtrans): VA/QRIS/transfer. Gunakan server-to-server webhook; verifikasi signature; idempotency; retry/backoff. Simpan pnbp_receipt_no, status, paid_at.
- API Ongkir (mis. RajaOngkir): hitung biaya kirim; simpan snapshot shipping_cost, courier_name, service, opsional tracking_number.
- Email: SMTP atau API; kirim pada event: order created, payment paid, shipped, completed.
- Semua komunikasi eksternal HTTPS + timeouts + logging. Rate-limit endpoint publik (tracking) (SKPL Website UPBS BRMP Biogen).
6) Aturan Bisnis Utama
- Tidak ada akun pembeli. Simpan snapshot minimal (Nama, Alamat, No. HP, opsional Email) per order.
- Checkout wajib: nama, alamat, no. HP; email disarankan untuk invoice/link tracking.
- Admin harus login untuk seluruh aksi (kecuali logout).
- Status order: awaiting_payment → paid → processing → shipped → completed (+ cancelled bila perlu). Perubahan manual oleh admin di panel sesuai SKPL (SKPL Website UPBS BRMP Biogen).
- Pembayaran: status “paid” hanya diset dari webhook yang valid. Admin tak boleh memaksa “paid” tanpa alasan audit.
7) Dokumen & Ekspor
- PDF: pilih satu—barryvdh/laravel-dompdf atau wkhtmltopdf (snappy). Buat template Invoice & Surat Jalan.
- CSV export: filter rentang tanggal & status; gunakan stream response; encoding UTF-8+BOM bila dibuka di Excel.
8) Keamanan & Kepatuhan
- Validasi semua input (Form Request).
- CSRF aktif untuk form; verifikasi signature pada webhook.
- Simpan audit log untuk create/update/delete pada produk, kategori, stok, status pesanan & dokumen; rekam user_id, waktu, IP, route, before/after JSON (ringkas).
- Terapkan rate-limit untuk: checkout, tracking, webhook (per IP/route).
- Pseudonimkan data sensitif di log aplikasi.
- Bahasa: Seluruh antarmuka dan konten website wajib menggunakan bahasa Inggris. Semua teks pada tampilan aplikasi (Blade templates, error messages, tombol, dsb.) harus konsisten dalam bahasa Inggris.
9) Struktur Proyek & Konvensi
- Namespace Controller:
    App\Http\Controllers\Admin\* dan App\Http\Controllers\Client\*.
- Views: resources/views/admin/*, resources/views/client/*.
- Routing: prefix /admin + middleware auth; publik: katalog, cart, checkout, tracking, webhook.
- Seeder: kategori BS, FS; beberapa produk contoh; admin default (ubah di .env).
- .env.example harus memuat kunci: DB_, MAIL_, PAYMENT_, ONGKIR_, APP_URL, TIMEZONE.
10) Testing & QA
- Minimal:
    - Feature test: checkout alur lengkap sampai membuat orders & order_items.
    - Webhook test: valid signature → set “paid”; invalid → 401/403.
    - Tracking test: ID valid/invalid.
- Gunakan database transactions di test; factories untuk product/order.
11) Dokumentasi Dev
- Tambahkan docs/ berisi: ERD, sequence transaksi (checkout→payment→fulfillment), mapping ID SKPL ↔ route/controller.
- Setiap PR besar harus menyertakan CHANGELOG ringkas dan dampak migrasi.
