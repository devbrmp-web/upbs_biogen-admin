# Master Guide: PDF Invoice System (SKPL-WUB-OUT-036)

Panduan komprehensif implementasi, arsitektur, dan kustomisasi sistem Invoice PDF untuk UPBS Biogen Admin.

## 🛠 Technical Stack
Sistem ini dibangun menggunakan pustaka backend Laravel yang telah divalidasi untuk stabilitas dokumen legal:
- **Framework**: Laravel 11
- **PDF Engine**: `barryvdh/laravel-dompdf` (Wrapper Dompdf)
- **QR Code Generator**: `simplesoftwareio/simple-qrcode`
- **Typography**: `DejaVu Sans` (Digunakan karena dukungan simbol mata uang 'Rp' yang stabil dan UTF-8 compatibility).

## 📂 File Structure
Arsitektur sistem ini terdistribusi pada beberapa layer untuk kemudahan pemeliharaan:
- **Controller**: `app/Http/Controllers/Admin/OrderController.php` (Logika kalkulasi & data injection).
- **View**: `resources/views/admin/orders/pdf/invoice.blade.php` (Template HTML/Blade).
- **Styling**: `resources/css/pdf-invoice.scss` (Design Tokens & Layouting).
- **Routes**:
  - Download Tunggal: `admin.orders.invoice.download`
  - Download Bulk (ZIP): `admin.orders.invoice.bulk`

## 🧠 Business & Calculation Logic

### 1. Perhitungan Matematis (Akurat)
Kalkulasi dilakukan secara iteratif di Controller sebelum dikirim ke View untuk menjamin akurasi data finansial:
- **Subtotal Barang**: Total dari `quantity × price_at_order` untuk seluruh item.
- **Biaya Layanan (1%)**: 1% dari total Subtotal Barang.
- **Biaya Aplikasi**: Biaya tetap sebesar **Rp 2.500**.
- **Total Pembayaran**: `Subtotal + Biaya Layanan + Biaya Aplikasi`.

### 2. Logika Status (Watermark)
Visual indicator "LUNAS" atau "BELUM DIBAYAR" muncul berdasarkan status pesanan:
- **Whitelist "LUNAS" (Hijau)**: Muncul jika status pesanan adalah `paid`, `completed`, `pickup_ready`, atau `processing`.
- **"BELUM DIBAYAR" (Merah)**: Muncul untuk status lainnya (seperti `awaiting_payment` atau `pending_verification`).

## 🎨 Modern Agriculture UI Design

### Branding & Aset
- **Primary Color**: Forest Green (`#2E7D32`) mencerminkan identitas pertanian modern.
- **Logo**: Menggunakan Logo Kementerian Pertanian RI untuk kesan resmi dan prestisius.
- **Slogan**: *"Benih Berkualitas untuk Kedaulatan Pangan Bangsa"* pada footer dokumen.

### Optimasi 1-Halaman (One-Page Fit)
Untuk memastikan dokumen profesional dan hemat kertas, beberapa trik layouting diterapkan:
- **Font Size**: Dasar font diset pada **9.5pt** atau **10pt**.
- **Layout 2-Kolom**: Informasi Nomor Invoice dan Data Pembeli diletakkan berdampingan secara horizontal.
- **QR Code Management**: Ukuran QR Code divalidasi pada **70px - 80px** agar tetap terscan namun tidak memakan ruang vertikal.
- **CSS Avoid Break**: Menggunakan properti `page-break-inside: avoid` pada footer untuk mencegah elemen "nendang" ke halaman kedua.

## 🛠 Troubleshooting & Gotchas

### 1. Perbaikan Error "Imagick"
**Issue**: Penggunaan format `.format('png')` pada QrCode membutuhkan ekstensi PHP Imagick yang seringkali tidak aktif di server Laragon/Production.
**Solusi**: Gunakan format **SVG** yang di-encode ke Base64:
```php
<img src="data:image/svg+xml;base64,{{ base64_encode(QrCode::size(80)->generate(...)) }}">
```

### 2. Gambar Tidak Muncul
**Solusi**: Dompdf membutuhkan gambar dalam format **Base64**. Di Controller, gambar logo harus di-encode terlebih dahulu:
```php
$logoBase64 = base64_encode(file_get_contents(public_path('path/to/logo.png')));
```

### 3. Memory Limit (Bulk Generation)
**Issue**: Merender banyak PDF (Bulk) sekaligus dalam ZIP memakan memori besar.
**Solusi**: `OrderController` sudah di-set secara otomatis menggunakan `ini_set('memory_limit', '512M')` untuk proses Bulk Download.

---
> [!IMPORTANT]
> **Catatan Pemeliharaan**: Jika ingin mengubah margin atau layout, pastikan selalu menjalankan `php artisan view:clear` untuk melihat perubahan terbaru pada preview PDF.
