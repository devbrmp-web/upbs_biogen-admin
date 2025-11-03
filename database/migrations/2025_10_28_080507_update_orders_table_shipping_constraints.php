<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Update status enum to include new statuses
            $table->enum('status', [
                'awaiting_payment',
                'paid', 
                'processing',
                'pickup_ready',
                'delivery_coordination',
                'picked_up',
                'shipped',
                'completed',
                'cancelled'
            ])->default('awaiting_payment')->change();
            
            // Ensure shipping_method has proper constraints (already ENUM but ensure values)
            $table->enum('shipping_method', ['pickup', 'delivery'])->default('pickup')->change();
        });
        
        // Add price_at_order column to order_items for price snapshots
        Schema::table('order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('order_items', 'price_at_order')) {
                $table->decimal('price_at_order', 12, 2)->after('unit_price')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Revert status enum to original values
            $table->enum('status', [
                'awaiting_payment',
                'paid', 
                'processing',
                'shipped',
                'completed',
                'cancelled'
            ])->default('awaiting_payment')->change();
        });
        
        // Remove price_at_order column
        Schema::table('order_items', function (Blueprint $table) {
            if (Schema::hasColumn('order_items', 'price_at_order')) {
                $table->dropColumn('price_at_order');
            }
        });
    }
    
    private function indexExists(string $table, string $index): bool
    {
        $indexes = Schema::getConnection()->getDoctrineSchemaManager()
            ->listTableIndexes($table);
        return array_key_exists($index, $indexes);
    }
};
