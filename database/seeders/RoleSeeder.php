<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buat role dasar
        Role::updateOrCreate(
            ['id' => 1],
            ['name' => 'super_admin', 'description' => 'Super Administrator dengan akses penuh']
        );
        
        Role::updateOrCreate(
            ['id' => 2],
            ['name' => 'admin', 'description' => 'Administrator dengan akses terbatas']
        );
    }
}
