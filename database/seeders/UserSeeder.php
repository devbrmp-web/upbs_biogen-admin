<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Pastikan role super_admin (id=1) tersedia
        $superAdminRole = Role::query()->where('id', 1)->where('name', 'super_admin')->first();
        if (! $superAdminRole) {
            // Jika belum ada, buat role sesuai spesifikasi
            $superAdminRole = Role::updateOrCreate(
                ['id' => 1],
                ['name' => 'super_admin', 'description' => 'Super Administrator dengan akses penuh']
            );
        }

        // Hanya buat user super_admin jika BELUM ada
        $existingSuperAdmin = User::query()->where('role_id', $superAdminRole->id)->exists();
        if (! $existingSuperAdmin) {
            User::create([
                'name' => 'Super Admin',
                'email' => 'superadmin@upbs.local',
                'role_id' => $superAdminRole->id,
                'password_hash' => Hash::make('password'),
            ]);
            $this->command?->info('User super_admin awal dibuat: superadmin@upbs.local / password');
        } else {
            $this->command?->info('User super_admin sudah ada, seeder dilewati.');
        }
    }
}