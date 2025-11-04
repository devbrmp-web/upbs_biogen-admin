# Kebijakan SKU Varietas (WUB)

Tujuan: memastikan identitas unik setiap varietas untuk katalog, order, dan integrasi. Dokumen ini menjadi acuan Admin saat membuat/mengubah SKU dan developer saat validasi.

## Prinsip Utama
- Unik secara global pada tabel `varieties` (tanpa duplikasi, case-insensitive mengikuti kolasi DB).
- Format ringkas, mudah dibaca manusia, stabil (jarang diubah), tidak memuat spasi.
- Tidak bergantung pada status stok; SKU merepresentasikan identitas varietas.
- Slug tetap menjadi route key; SKU digunakan untuk operasional dan referensi eksternal.

## Format & Aturan
- Karakter diizinkan: huruf A–Z, angka 0–9, dan tanda minus `-`.
- Harus diawali huruf (`A–Z`).
- Panjang: 3–30 karakter.
- Disarankan UPPERCASE untuk konsistensi.
- Contoh yang baik: `CAB-INTAN-001`, `PIS-KEC-10`, `TIS-PLN-001`.
- Hindari kata cadangan: `TEST`, `DUMMY`, `SAMPLE`.

Regex rekomendasi validasi:
```
^[A-Z][A-Z0-9-]{2,29}$
```

## Kebijakan Perubahan
- Perubahan SKU diperbolehkan oleh Admin dengan alasan operasional, wajib AuditLog (aksi UPDATE, nilai lama vs baru).
- Dampak: referensi eksternal (csv, dokumen) harus diperbarui; sistem internal tidak menggunakan SKU sebagai route key.
- Disarankan minimalkan perubahan SKU setelah publikasi katalog.

## Rekomendasi Implementasi (Developer)
- Validasi Form (Store/Update):
  - `required|string|max:30|regex:/^[A-Z][A-Z0-9-]{2,29}$/|unique:varieties,sku` (Update: `Rule::unique()->ignore($id)`).
- Normalisasi input:
  - Trim spasi; konversi ke UPPERCASE sebelum simpan.
- Mutator opsional pada Model `Variety`:
```php
protected function sku(): Attribute
{
    return Attribute::make(
        set: fn ($value) => strtoupper(trim($value ?? '')),
    );
}
```
- DB:
  - Pastikan kolom `sku` bertipe `VARCHAR(100)` dengan unique index.
  - Kolasi default MySQL (`utf8mb4_*`) bersifat case-insensitive → cukup untuk keunikan tanpa membedakan case.
- Audit:
  - Catat perubahan SKU ke `audit_logs` pada `CREATE`/`UPDATE` varietas.

## QA / Uji
- Coba input invalid (spasi, huruf kecil diawal, karakter tak diizinkan, panjang <3 atau >30) → harus gagal.
- Coba duplikasi SKU beda case (`CAB-INTAN-001` vs `cab-intan-001`) → harus gagal.
- Coba ubah SKU pada varietas eksisting → sukses, ada entri audit.
- Pastikan tampilan katalog, pencarian dan order tetap berfungsi setelah perubahan SKU.

Referensi: SKPL-WUB (PRO – Manajemen Produk/Varietas, Validasi Input, AuditLog).