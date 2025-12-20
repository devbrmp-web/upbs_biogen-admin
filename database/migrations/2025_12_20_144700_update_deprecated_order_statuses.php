<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Migrate existing orders with deprecated statuses to the new simplified flow:
     * - delivery_coordination -> processing (needs admin to complete)
     * - shipped -> completed (already shipped = done)
     * - picked_up -> completed (already picked up = done)
     */
    public function up(): void
    {
        // Update delivery_coordination orders back to processing
        // Admin will need to manually transition them to completed
        DB::table('orders')
            ->where('status', 'delivery_coordination')
            ->update(['status' => 'processing']);

        // Update shipped orders to completed (they were in-flight, assume delivered)
        DB::table('orders')
            ->where('status', 'shipped')
            ->update([
                'status' => 'completed',
                'completed_at' => DB::raw('COALESCE(completed_at, updated_at)')
            ]);

        // Update picked_up orders to completed (they were picked, just complete them)
        DB::table('orders')
            ->where('status', 'picked_up')
            ->update([
                'status' => 'completed',
                'completed_at' => DB::raw('COALESCE(completed_at, updated_at)')
            ]);
    }

    /**
     * Reverse the migrations.
     * 
     * Note: This is a data migration, reversing it would require
     * knowing the original status which we don't preserve.
     * This rollback is a no-op.
     */
    public function down(): void
    {
        // Cannot reverse - original status data is lost
        // No-op rollback
    }
};
