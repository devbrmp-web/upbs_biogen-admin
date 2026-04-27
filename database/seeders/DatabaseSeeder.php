<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * ══════════════════════════════════════════════════════════════
     *  PRODUCTION-READY DATABASE SEED — UPBS BIOGEN 2026
     * ══════════════════════════════════════════════════════════════
     *
     * Urutan eksekusi seeder (dependency order):
     *   1. RoleSeeder          → Buat roles (super_admin, admin)
     *   2. AdminUserSeeder     → Buat user: admin@biogen.com & superadmin@upbs.test
     *   3. SeedClassSeeder     → Buat 6 kelas benih (BS, FS, SS, ST, G0, BSM)
     *   4. CommoditySeeder     → Buat 7 komoditas riil Biogen
     *   5. VarietySeeder       → Buat varietas lengkap per komoditas (deskripsi riil)
     *   6. SeedLotSeeder       → Injeksi stok riil (Excel 2026) + dummy FS/SS/Hortikultura
     *   7. OrderTransactionSeeder → 8 sampel transaksi untuk grafik Dashboard
     *
     * Sumber data:
     *  - Buku Saku BSIP Biogen 2024
     *  - Penetapan PNBP PPHP BRMP Biogen (PNBP 2026)
     *  - Rekapitulasi Pelayanan Publik 2026 (Excel)
     *
     * Jalankan dengan: php artisan migrate:fresh --seed
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            AdminUserSeeder::class,
            SeedClassSeeder::class,
            CommoditySeeder::class,
            VarietySeeder::class,
            SeedLotSeeder::class,
            OrderTransactionSeeder::class,
        ]);

        $this->command->newLine();
        $this->command->info('═══════════════════════════════════════════════════════════');
        $this->command->info('  ✅ UPBS BIOGEN — Database production-ready berhasil!');
        $this->command->info('═══════════════════════════════════════════════════════════');
        $this->command->line('  Login Admin  : admin@biogen.com / password');
        $this->command->line('  Login SA     : superadmin@upbs.test / password');
        $this->command->info('═══════════════════════════════════════════════════════════');
    }
}
