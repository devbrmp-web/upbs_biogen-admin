<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Gunakan variabel lingkungan untuk kredensial admin
        $adminEmail = env('ADMIN_EMAIL', 'admin@upbs.local');
        $adminName = env('ADMIN_NAME', 'Administrator');
        $adminPassword = env('ADMIN_PASSWORD', 'admin123');
        $adminRoleId = env('ADMIN_ROLE_ID', 2); // default: admin role
        
        // Buat atau perbarui admin
        User::updateOrCreate(
            ['email' => $adminEmail],
            [
                'name' => $adminName,
                'role_id' => $adminRoleId,
                'password_hash' => Hash::make($adminPassword),
                'remember_token' => null
            ]
        );
        
        // Log informasi (tanpa password)
        $this->command->info("Admin user {$adminEmail} berhasil dibuat/diperbarui");
    }
}
