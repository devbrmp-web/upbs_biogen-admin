Varietas: Validasi Input & Perhitungan Stok (BS/FS/Planlet)

Ringkasan
- BS/FS (satuan kg) sekarang mendukung desimal hingga 3 angka di belakang koma, selaras dengan skema database.
- Planlet dan Minimum Limit tetap bilangan bulat (integer) dengan batas minimum 0.
- SKU bersifat opsional; akan dibuat otomatis jika kosong.

Back-end (Controller & Model)
- Controller (VarietyController@store, @update):
  - stock_bs_kg: nullable|numeric|min:0
  - stock_fs_kg: nullable|numeric|min:0
  - planlet: nullable|integer|min:0
  - minimum_limit: nullable|integer|min:0
  - Normalisasi: nilai null untuk stok/planlet/minimum_limit diset menjadi 0 sebelum disimpan/diupdate.
- Model (app/Models/Variety.php):
  - Casts: stock_bs_kg => decimal:3, stock_fs_kg => decimal:3, planlet => integer, minimum_limit => integer.
  - Otomatisasi: slug dan sku dibuat otomatis jika kosong.
  - Atribut turunan: total_stock dapat dihitung sebagai stock_bs_kg + stock_fs_kg (di controller/view atau accessor).

Front-end (Views & JS)
- Create/Edit Views:
  - BS/FS: type="number", step="0.001", inputmode="decimal", placeholder mencerminkan dukungan desimal.
  - Planlet/Minimum Limit: type="number", step="1", inputmode="numeric".
  - Label/Help text diperbarui: BS/FS menerima angka desimal (maks 3 digit). Planlet/Minimum Limit harus bilangan bulat.
- Sanitasi JavaScript:
  - Desimal (BS/FS): mengizinkan titik atau koma, mengonversi koma ke titik, mencegah lebih dari satu titik, hanya digit dan satu titik desimal, min 0.
  - Integer (Planlet/Minimum Limit): hanya digit, min 0.
  - Event: input, change, keyup untuk menjaga konsistensi nilai saat pengguna mengetik.

Database & Migrasi
- Kolom BS/FS bertipe DECIMAL(10,3) di migrasi varietas sehingga menyimpan hingga 3 desimal tanpa pembulatan tak terduga.
- Kolom planlet ditambahkan via migrasi add_planlet_to_varieties_table sebagai integer (non-negatif).

Pengujian
- Unit/Feature tests mencakup:
  - Validasi nilai negatif ditolak (price, BS/FS, minimum_limit).
  - SKU tidak wajib; tidak muncul pada errors ketika kosong.
  - Perhitungan total_stock menjumlahkan BS + FS termasuk nilai desimal.
  - Status stok (Habis/Restock/Tersedia) ditentukan dari total_stock dan minimum_limit.
- Saat ini seluruh 148 pengujian lolos.

Format & Tampilan Angka
- BS/FS ditampilkan tanpa pemisah ribuan, dengan 3 desimal bila relevan.
- Planlet/Minimum Limit ditampilkan sebagai integer.
- Untuk konsistensi, gunakan number_format($value, 3) untuk kg dan (int)$value untuk integer saat perlu pemformatan eksplisit di view.

Catatan Locale
- Input menerima titik atau koma sebagai pemisah desimal; JS mengonversi koma menjadi titik agar sesuai dengan numeric backend.
- Saat rendering server-side, gunakan titik sebagai pemisah desimal.

Saran Lanjutan
- Tambahkan accessor getTotalStockAttribute() di Model untuk konsistensi perhitungan di seluruh aplikasi.
- Tambahkan tampilan "Total Stock (kg)" yang ter-update dinamis di halaman create/edit.
- Pertimbangkan helper formatDecimal($value, $precision=3) untuk tampilan konsisten di seluruh view.