# Black Box Testing Summary - UPBS Biogen E-Commerce

## Overview

| Atribut | Detail |
|---------|--------|
| **Sistem** | UPBS Biogen E-Commerce |
| **Total Test Cases Admin** | 50 test cases |
| **Total Test Cases Client** | 30 test cases |
| **Grand Total** | **80 test cases** |
| **Metode Pengujian** | Black Box Testing |
| **Tipe Pengujian** | Functional Testing + User Acceptance Testing (UAT) |
| **Tanggal Dihasilkan** | 20 April 2026 |
| **Standar Referensi** | IEEE 829, ISTQB Foundation Level |

---

## Coverage Analysis

### Admin Module Coverage (`upbs_biogen-admin`)

| No | Module | Fitur yang Dicakup | Jumlah Test Cases | Coverage |
|----|--------|--------------------|-------------------|----------|
| 1 | Authentication | Login (valid, invalid, field kosong), Logout, Proteksi halaman | 7 | 100% |
| 2 | BI Dashboard | Statistik pesanan, Filter tanggal, Low Stock Alert, Ekspor CSV dashboard | 4 | 100% |
| 3 | Admin Management | View daftar, Tambah admin, Edit admin, Hapus admin, Proteksi hapus akun sendiri | 5 | 100% |
| 4 | Commodities Management | View list, Tambah, Edit, Hapus (dengan constraint varieties) | 5 | 100% |
| 5 | Varieties Management | View list, Tambah dengan gambar, Edit, Hapus (dengan constraint order) | 5 | 100% |
| 6 | Seed Class Management | View list, Tambah, Edit, Hapus (dengan constraint seed lot) | 5 | 100% |
| 7 | Seed Lots Management | View list + filter, Tambah BS/FS, Edit, Hapus | 4 | 100% |
| 8 | Order Management | View list + filter, Cari, Detail, Update status (valid), Update status (invalid), Batalkan, Hapus, Proteksi hapus | 8 | 100% |
| 9 | Fulfillment | Alur Delivery (Paid→Processing→Completed), Alur Pickup (Paid→Processing→Pickup Ready→Completed) | 2 | 100% |
| 10 | Dokumen Transaksi (PDF) | Download invoice PDF single order | 1 | 100% |
| 11 | Ekspor Laporan (CSV) | Export data pesanan ke CSV | 1 | 100% |
| 12 | Audit Logs | View list, Filter per kategori & aksi, View detail log | 3 | 100% |
| **TOTAL** | | | **50** | **100%** |

### Client Module Coverage (`upbs_biogen-client`)

| No | Module | Fitur yang Dicakup | Jumlah Test Cases | Coverage |
|----|--------|--------------------|-------------------|----------|
| 1 | Onboarding | Tutorial interaktif pertama kali, Halaman tutorial | 2 | 100% |
| 2 | Katalog | View daftar varietas, Filter komoditas, Filter kelas benih, Pencarian, Detail produk, Harga dinamis | 6 | 100% |
| 3 | Cart Management | Tambah ke keranjang, View keranjang, Update quantity, Hapus item, State keranjang kosong | 5 | 100% |
| 4 | Checkout | Guest checkout, Validasi form, Metode pengiriman Pickup | 3 | 100% |
| 5 | Payment | View instruksi pembayaran, Upload bukti (sukses), Upload file terlalu besar, Upload format tidak valid | 4 | 100% |
| 6 | Order Tracking | Track by kode pesanan, Track by nomor telepon, Kode tidak valid, Detail pesanan, Receipt | 5 | 100% |
| 7 | Help & FAQ | FAQ, Tentang Kami, Kontak, Kebijakan Privasi, Syarat & Ketentuan | 5 | 100% |
| **TOTAL** | | | **30** | **100%** |

---

## Test Scenario Type Distribution

### Admin Repository

| Tipe Skenario | Jumlah | Persentase |
|---------------|--------|------------|
| Happy Path (skenario normal) | 30 | 60% |
| Negative Testing (input invalid, boundary) | 14 | 28% |
| Security (unauthorized access, proteksi) | 3 | 6% |
| Edge Cases (data constraint, state boundary) | 3 | 6% |
| **Total** | **50** | **100%** |

### Client Repository

| Tipe Skenario | Jumlah | Persentase |
|---------------|--------|------------|
| Happy Path (skenario normal) | 20 | 67% |
| Negative Testing (input invalid, boundary) | 5 | 17% |
| Edge Cases (state kosong, constraint) | 5 | 16% |
| **Total** | **30** | **100%** |

---

## Testing Guidelines

### Prerequisites

#### Environment
1. **Server**: Development/Staging environment aktif di Laragon
2. **URL Admin**: `http://upbs_biogen-admin.test` atau sesuai konfigurasi `.env`
3. **URL Client**: `http://upbs_biogen-client.test` atau sesuai konfigurasi `.env`
4. **Database**: Database terisi dengan data awal (seed) yang memadai
5. **Storage**: Pastikan direktori `storage/app/public` writable dan symbolic link sudah dibuat

#### Browser yang Didukung
- Google Chrome (versi terbaru)
- Mozilla Firefox (versi terbaru)
- Microsoft Edge (versi terbaru)

#### Test Data yang Diperlukan

**Untuk Admin:**
- Minimal 1 akun Super Admin aktif
- Minimal 1 akun Admin biasa aktif
- Minimal 2 komoditas data
- Minimal 3 varietas benih dengan gambar
- Minimal 2 kelas benih (contoh: BS, FS)
- Minimal 3 seed lot dengan stok yang bervariasi
- Minimal 3 pesanan dengan status yang berbeda-beda (Awaiting Payment, Processing, Completed, Cancelled)

**Untuk Client:**
- Seed lot aktif dengan `is_sellable = true` dan stok > 0
- Varietas yang memiliki gambar dan deskripsi lengkap
- Kode pesanan yang valid untuk pengujian tracking
- File gambar test (JPG/PNG < 10MB) untuk upload bukti bayar
- File gambar test (> 10MB) untuk uji batas ukuran file

---

### Execution Steps

#### Cara Menggunakan File TSV di Excel

1. **Download** file TSV dari folder `docs/`:
   - `blackbox_test_cases_admin.tsv`
   - `blackbox_test_cases_client.tsv`

2. **Buka Excel** (Microsoft Excel atau Google Sheets)

3. **Import TSV** ke Excel:
   - Di Excel: Buka file TSV langsung (Excel akan otomatis parsing kolom)
   - Di Google Sheets: File > Import > Upload > pilih file TSV > Separator: Tab

4. **Atur lebar kolom** agar konten terbaca dengan baik (terutama kolom Langkah Uji dan Kriteria Penerimaan)

5. **Isi kolom "Hasil"** dengan salah satu status berikut:
   - `Lulus` — Test case berhasil dan memenuhi kriteria penerimaan
   - `Tidak Lulus` — Test case gagal atau tidak memenuhi kriteria penerimaan
   - `Belum diuji` — Test case belum dieksekusi (nilai default)
   - `Dilewati` — Test case sengaja dilewati karena kondisi tidak terpenuhi

6. **Dokumentasikan temuan bug** pada sheet terpisah dengan informasi:
   - No. Test Case yang gagal
   - Screenshot error
   - Langkah replikasi bug
   - Tingkat keparahan (Critical/Major/Minor)

---

## Referensi Fitur dari UAT Diagrams

### Admin (dari UAT Aktor Admin)

| No | Fitur UAT | Status di Test Cases |
|----|-----------|---------------------|
| 1 | Login | ✅ Dicakup (TC-1 s.d. TC-6) |
| 2 | Logout | ✅ Dicakup (TC-7) |
| 3 | BI Dashboard | ✅ Dicakup (TC-8 s.d. TC-11) |
| 4 | Admin Management - Add | ✅ Dicakup (TC-13) |
| 5 | Admin Management - View | ✅ Dicakup (TC-12) |
| 6 | Admin Management - Edit | ✅ Dicakup (TC-14) |
| 7 | Admin Management - Delete | ✅ Dicakup (TC-15, TC-16) |
| 8 | Commodities Management - Add | ✅ Dicakup (TC-18) |
| 9 | Commodities Management - View | ✅ Dicakup (TC-17) |
| 10 | Commodities Management - Edit | ✅ Dicakup (TC-19) |
| 11 | Commodities Management - Delete | ✅ Dicakup (TC-20, TC-21) |
| 12 | Varieties Management - Add | ✅ Dicakup (TC-23) |
| 13 | Varieties Management - View | ✅ Dicakup (TC-22) |
| 14 | Varieties Management - Edit | ✅ Dicakup (TC-24) |
| 15 | Varieties Management - Delete | ✅ Dicakup (TC-25, TC-26) |
| 16 | Seed Class Management - Add | ✅ Dicakup (TC-28) |
| 17 | Seed Class Management - View | ✅ Dicakup (TC-27) |
| 18 | Seed Class Management - Edit | ✅ Dicakup (TC-29) |
| 19 | Seed Class Management - Delete | ✅ Dicakup (TC-30, TC-31) |
| 20 | Seed Lots Management - Add | ✅ Dicakup (TC-33) |
| 21 | Seed Lots Management - View | ✅ Dicakup (TC-32) |
| 22 | Seed Lots Management - Edit | ✅ Dicakup (TC-34) |
| 23 | Seed Lots Management - Delete | ✅ Dicakup (TC-35) |
| 24 | Order Management - View | ✅ Dicakup (TC-36, TC-37, TC-38) |
| 25 | Order Management - Edit Status | ✅ Dicakup (TC-39, TC-40) |
| 26 | Order Management - Delete | ✅ Dicakup (TC-42, TC-43) |
| 27 | Fulfillment - Full Workflow | ✅ Dicakup (TC-44, TC-45) |
| 28 | Dokumen Transaksi (PDF Invoice) | ✅ Dicakup (TC-46) |
| 29 | Ekspor Laporan (CSV) | ✅ Dicakup (TC-47) |
| 30 | Audit Logs | ✅ Dicakup (TC-48, TC-49, TC-50) |

### Client (dari UAT Aktor Client)

| No | Fitur UAT | Status di Test Cases |
|----|-----------|---------------------|
| 1 | Onboarding (Interactive Tour) | ✅ Dicakup (TC-1, TC-2) |
| 2 | Katalog - Varietas Benih | ✅ Dicakup (TC-3, TC-4, TC-5) |
| 3 | Katalog - Detail Produk | ✅ Dicakup (TC-7, TC-8) |
| 4 | Cart - Add to Cart | ✅ Dicakup (TC-9) |
| 5 | Cart - View Cart | ✅ Dicakup (TC-10) |
| 6 | Cart - Update Quantity | ✅ Dicakup (TC-11) |
| 7 | Cart - Delete Item | ✅ Dicakup (TC-12) |
| 8 | Checkout (Guest Checkout + Shipping) | ✅ Dicakup (TC-14, TC-15, TC-16) |
| 9 | Payment (Transfer Bank Manual + Upload Bukti) | ✅ Dicakup (TC-17, TC-18, TC-19, TC-20) |
| 10 | Order Tracking (Track by Order ID) | ✅ Dicakup (TC-21, TC-22, TC-23, TC-24, TC-25) |
| 11 | Help & FAQ | ✅ Dicakup (TC-26, TC-27, TC-28, TC-29, TC-30) |

---

## Status Kesiapan Testing

| Kriteria | Status |
|----------|--------|
| Format Excel-ready (TSV) | ✅ Ya |
| 6 Kolom sesuai format yang diminta | ✅ Ya |
| Bahasa Indonesia formal | ✅ Ya |
| Read-only access dipatuhi (tidak ada file yang diedit) | ✅ Ya |
| Siap untuk laporan TA | ✅ Ya |
| Mencakup Happy Path, Negative Testing, Edge Cases | ✅ Ya |
| Semua fitur dari UAT Admin dicakup (30 fitur) | ✅ Ya (50 TC) |
| Semua fitur dari UAT Client dicakup (11 fitur) | ✅ Ya (30 TC) |
| Langkah uji spesifik dan reproducible | ✅ Ya |
| Kriteria penerimaan measurable | ✅ Ya |

---

## File Output yang Dihasilkan

| File | Lokasi | Deskripsi |
|------|--------|-----------|
| `blackbox_test_cases_admin.tsv` | `upbs_biogen-admin/docs/` | 50 test cases untuk sistem Admin |
| `blackbox_test_cases_client.tsv` | `upbs_biogen-admin/docs/` | 30 test cases untuk sistem Client |
| `blackbox_testing_summary.md` | `upbs_biogen-admin/docs/` | Dokumen ringkasan ini |

---

## Metodologi

Dokumen test cases ini dibuat menggunakan metodologi **Black Box Testing** dengan teknik:

1. **Equivalence Partitioning** — Input dibagi menjadi partisi valid dan invalid untuk menguji representasi setiap kelompok
2. **Boundary Value Analysis** — Pengujian nilai batas (contoh: ukuran file maksimal, jumlah karakter maksimal)
3. **Decision Table Testing** — Digunakan untuk fitur dengan kombinasi kondisi (contoh: transisi status pesanan)
4. **State Transition Testing** — Digunakan untuk alur status pesanan (Pending → Paid → Processing → Completed)
5. **Error Guessing** — Berdasarkan analisis kode controller untuk mengidentifikasi skenario error yang mungkin terjadi

---

*Dokumen ini dihasilkan secara otomatis melalui analisis kode sumber (READ-ONLY) pada:*
- `upbs_biogen-admin/routes/web.php`
- `upbs_biogen-admin/app/Http/Controllers/Admin/*.php`
- `upbs_biogen-admin/app/Http/Controllers/Auth/AuthenticatedSessionController.php`
- `upbs_biogen-client/routes/web.php`
- `upbs_biogen-client/app/Http/Controllers/*.php`

*Tanggal Analisis: 20 April 2026*
