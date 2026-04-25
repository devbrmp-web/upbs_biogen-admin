<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Jalankan seeder roles terlebih dahulu, kemudian superadmin sebelum admin biasa
        $this->call([
            RoleSeeder::class,
            UserSeeder::class,        // Creates superadmin first
            AdminUserSeeder::class,   // Creates regular admin after superadmin
            CommoditySeeder::class,
            VarietySeeder::class,
            SeedClassSeeder::class,
            SeedLotSeeder::class,
            DemoDataSeeder::class,
            StarterSeedLotSeeder::class,
            DemoOrderSeeder::class,
        ]);
    }
}
