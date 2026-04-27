<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Membuat user admin utama: admin@biogen.com / password
     * dan memastikan super_admin default tetap tersedia.
     */
    public function run(): void
    {
        // ── Super Admin default ───────────────────────────────────────────────
        $superAdminRole = Role::updateOrCreate(
            ['id' => 1],
            ['name' => 'super_admin', 'description' => 'Super Administrator dengan akses penuh']
        );

        User::updateOrCreate(
            ['email' => 'superadmin@upbs.test'],
            [
                'name'     => 'Super Admin Biogen',
                'role_id'  => $superAdminRole->id,
                'password' => Hash::make('password'),
            ]
        );

        // ── Admin utama (production-ready) ────────────────────────────────────
        $adminRole = Role::updateOrCreate(
            ['id' => 2],
            ['name' => 'admin', 'description' => 'Administrator dengan akses terbatas']
        );

        User::updateOrCreate(
            ['email' => 'admin@biogen.com'],
            [
                'name'     => 'Admin UPBS Biogen',
                'role_id'  => $adminRole->id,
                'password' => Hash::make('password'),
            ]
        );

        $this->command->info('✅ AdminUserSeeder:');
        $this->command->line('   → superadmin@upbs.test / password  (super_admin)');
        $this->command->line('   → admin@biogen.com / password       (admin)');
    }
}
