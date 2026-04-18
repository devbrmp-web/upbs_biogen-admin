<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Seeder;

class AuditLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::first();
        if (!$admin) {
            // Create a dummy admin if none exists
            $admin = User::factory()->create([
                'name' => 'Admin User',
                'email' => 'admin_test@example.com',
            ]);
        }

        // 1. Login Log
        AuditLog::create([
            'user_id' => $admin->id,
            'action' => AuditLog::ACTION_LOGIN,
            'table_name' => 'users',
            'record_id' => $admin->id,
            'description' => "User logged in: {$admin->email}",
            'category' => AuditLog::CATEGORY_AUTHENTICATION,
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'created_at' => now()->subHours(2),
        ]);

        // 2. Create Variety Log
        AuditLog::create([
            'user_id' => $admin->id,
            'action' => AuditLog::ACTION_CREATE,
            'table_name' => 'varieties',
            'record_id' => 101,
            'old_data' => null,
            'new_data' => [
                'name' => 'Ciherang', 
                'commodity_id' => 1,
                'description' => 'Benih Padi Unggul',
                'price' => 50000
            ],
            'description' => 'Created variety record',
            'category' => AuditLog::CATEGORY_INVENTORY_MANAGEMENT,
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'created_at' => now()->subHours(1)->subMinutes(30),
        ]);

        // 3. Update Variety Log
        AuditLog::create([
            'user_id' => $admin->id,
            'action' => AuditLog::ACTION_UPDATE,
            'table_name' => 'varieties',
            'record_id' => 101,
            'old_data' => ['price' => 50000],
            'new_data' => ['price' => 55000],
            'description' => 'Updated varieties record',
            'category' => AuditLog::CATEGORY_INVENTORY_MANAGEMENT,
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'created_at' => now()->subHours(1),
        ]);

        // 4. Create Order Log
        AuditLog::create([
            'user_id' => $admin->id, // Maybe system or admin creating order
            'action' => AuditLog::ACTION_CREATE,
            'table_name' => 'orders',
            'record_id' => 1001,
            'old_data' => null,
            'new_data' => [
                'order_number' => 'ORD-20251209-001',
                'total_amount' => 150000,
                'status' => 'pending'
            ],
            'description' => 'Created orders record',
            'category' => AuditLog::CATEGORY_ORDER_MANAGEMENT,
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'created_at' => now()->subMinutes(45),
        ]);

        // 5. Delete Log
        AuditLog::create([
            'user_id' => $admin->id,
            'action' => AuditLog::ACTION_DELETE,
            'table_name' => 'seed_lots',
            'record_id' => 5,
            'old_data' => ['lot_number' => 'LOT-123', 'qty' => 0],
            'new_data' => null,
            'description' => 'Deleted seed_lots record',
            'category' => AuditLog::CATEGORY_INVENTORY_MANAGEMENT,
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'created_at' => now()->subMinutes(10),
        ]);
    }
}
