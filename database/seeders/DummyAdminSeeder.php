<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DummyAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Creates dummy admin users (role_id = 2) for pagination testing.
     * Only creates regular admins, never Super Admins.
     */
    public function run(): void
    {
        // Only create dummy data if we have less than 20 regular admins
        $currentAdminCount = User::where('role_id', 2)->count();
        
        if ($currentAdminCount >= 20) {
            $this->command->info('Skipping dummy admin creation - already have sufficient admin users for testing.');
            return;
        }
        
        $dummyAdmins = [
            [
                'name' => 'Ahmad Wijaya',
                'email' => 'ahmad.wijaya@brmp.go.id',
                'password' => Hash::make('password123'),
                'role_id' => 2,
                'created_at' => now()->subDays(30),
                'updated_at' => now()->subDays(30),
            ],
            [
                'name' => 'Siti Nurhaliza',
                'email' => 'siti.nurhaliza@brmp.go.id',
                'password' => Hash::make('password123'),
                'role_id' => 2,
                'created_at' => now()->subDays(25),
                'updated_at' => now()->subDays(25),
            ],
            [
                'name' => 'Budi Santoso',
                'email' => 'budi.santoso@brmp.go.id',
                'password' => Hash::make('password123'),
                'role_id' => 2,
                'created_at' => now()->subDays(20),
                'updated_at' => now()->subDays(20),
            ],
            [
                'name' => 'Dewi Kartika',
                'email' => 'dewi.kartika@brmp.go.id',
                'password' => Hash::make('password123'),
                'role_id' => 2,
                'created_at' => now()->subDays(18),
                'updated_at' => now()->subDays(18),
            ],
            [
                'name' => 'Eko Prasetyo',
                'email' => 'eko.prasetyo@brmp.go.id',
                'password' => Hash::make('password123'),
                'role_id' => 2,
                'created_at' => now()->subDays(15),
                'updated_at' => now()->subDays(15),
            ],
            [
                'name' => 'Fitri Handayani',
                'email' => 'fitri.handayani@brmp.go.id',
                'password' => Hash::make('password123'),
                'role_id' => 2,
                'created_at' => now()->subDays(12),
                'updated_at' => now()->subDays(12),
            ],
            [
                'name' => 'Gunawan Setiawan',
                'email' => 'gunawan.setiawan@brmp.go.id',
                'password' => Hash::make('password123'),
                'role_id' => 2,
                'created_at' => now()->subDays(10),
                'updated_at' => now()->subDays(10),
            ],
            [
                'name' => 'Hani Rahmawati',
                'email' => 'hani.rahmawati@brmp.go.id',
                'password' => Hash::make('password123'),
                'role_id' => 2,
                'created_at' => now()->subDays(8),
                'updated_at' => now()->subDays(8),
            ],
            [
                'name' => 'Indra Kusuma',
                'email' => 'indra.kusuma@brmp.go.id',
                'password' => Hash::make('password123'),
                'role_id' => 2,
                'created_at' => now()->subDays(6),
                'updated_at' => now()->subDays(6),
            ],
            [
                'name' => 'Joko Widodo',
                'email' => 'joko.widodo@brmp.go.id',
                'password' => Hash::make('password123'),
                'role_id' => 2,
                'created_at' => now()->subDays(5),
                'updated_at' => now()->subDays(5),
            ],
            [
                'name' => 'Kartini Sari',
                'email' => 'kartini.sari@brmp.go.id',
                'password' => Hash::make('password123'),
                'role_id' => 2,
                'created_at' => now()->subDays(4),
                'updated_at' => now()->subDays(4),
            ],
            [
                'name' => 'Lukman Hakim',
                'email' => 'lukman.hakim@brmp.go.id',
                'password' => Hash::make('password123'),
                'role_id' => 2,
                'created_at' => now()->subDays(3),
                'updated_at' => now()->subDays(3),
            ],
            [
                'name' => 'Maya Sari',
                'email' => 'maya.sari@brmp.go.id',
                'password' => Hash::make('password123'),
                'role_id' => 2,
                'created_at' => now()->subDays(2),
                'updated_at' => now()->subDays(2),
            ],
            [
                'name' => 'Nanda Pratama',
                'email' => 'nanda.pratama@brmp.go.id',
                'password' => Hash::make('password123'),
                'role_id' => 2,
                'created_at' => now()->subDays(1),
                'updated_at' => now()->subDays(1),
            ],
            [
                'name' => 'Oki Setiawan',
                'email' => 'oki.setiawan@brmp.go.id',
                'password' => Hash::make('password123'),
                'role_id' => 2,
                'created_at' => now()->subHours(12),
                'updated_at' => now()->subHours(12),
            ],
            [
                'name' => 'Putri Maharani',
                'email' => 'putri.maharani@brmp.go.id',
                'password' => Hash::make('password123'),
                'role_id' => 2,
                'created_at' => now()->subHours(6),
                'updated_at' => now()->subHours(6),
            ],
            [
                'name' => 'Qori Sumantri',
                'email' => 'qori.sumantri@brmp.go.id',
                'password' => Hash::make('password123'),
                'role_id' => 2,
                'created_at' => now()->subHours(3),
                'updated_at' => now()->subHours(3),
            ],
            [
                'name' => 'Rina Susanti',
                'email' => 'rina.susanti@brmp.go.id',
                'password' => Hash::make('password123'),
                'role_id' => 2,
                'created_at' => now()->subHours(1),
                'updated_at' => now()->subHours(1),
            ],
            [
                'name' => 'Sandi Permana',
                'email' => 'sandi.permana@brmp.go.id',
                'password' => Hash::make('password123'),
                'role_id' => 2,
                'created_at' => now()->subMinutes(30),
                'updated_at' => now()->subMinutes(30),
            ],
            [
                'name' => 'Tari Wulandari',
                'email' => 'tari.wulandari@brmp.go.id',
                'password' => Hash::make('password123'),
                'role_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($dummyAdmins as $admin) {
            // Only create if email doesn't exist
            User::firstOrCreate(
                ['email' => $admin['email']],
                $admin
            );
        }

        $this->command->info('Created dummy admin users for pagination testing.');
    }
}