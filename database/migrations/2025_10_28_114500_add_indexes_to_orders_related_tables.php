<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Helper to check index existence (MySQL only). For other drivers, assume not exists.
        $driver = DB::getDriverName();
        $hasIndex = function(string $table, string $indexName) use ($driver): bool {
            if ($driver === 'mysql') {
                $res = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
                return count($res) > 0;
            }
            if ($driver === 'sqlite') {
                $indexes = DB::select('PRAGMA index_list("'.$table.'")');
                foreach ($indexes as $idx) {
                    // For SQLite, the index name is in $idx->name
                    if (isset($idx->name) && $idx->name === $indexName) {
                        return true;
                    }
                }
                return false;
            }
            // For other drivers, assume not exists
            return false;
        };

        Schema::table('orders', function (Blueprint $table) use ($hasIndex) {
            if (!$hasIndex('orders', 'orders_order_code_index')) {
                $table->index('order_code', 'orders_order_code_index');
            }
            if (!$hasIndex('orders', 'orders_status_index')) {
                $table->index('status', 'orders_status_index');
            }
            if (!$hasIndex('orders', 'orders_shipping_method_index')) {
                $table->index('shipping_method', 'orders_shipping_method_index');
            }
            if (!$hasIndex('orders', 'orders_created_at_index')) {
                $table->index('created_at', 'orders_created_at_index');
            }
        });

        Schema::table('order_items', function (Blueprint $table) use ($hasIndex) {
            if (!$hasIndex('order_items', 'order_items_order_id_index')) {
                $table->index('order_id', 'order_items_order_id_index');
            }
        });

        Schema::table('payments', function (Blueprint $table) use ($hasIndex) {
            if (!$hasIndex('payments', 'payments_order_id_index')) {
                $table->index('order_id', 'payments_order_id_index');
            }
            if (!$hasIndex('payments', 'payments_status_index')) {
                $table->index('status', 'payments_status_index');
            }
        });

        Schema::table('shipments', function (Blueprint $table) use ($hasIndex) {
            if (!$hasIndex('shipments', 'shipments_order_id_index')) {
                $table->index('order_id', 'shipments_order_id_index');
            }
            if (!$hasIndex('shipments', 'shipments_status_index')) {
                $table->index('status', 'shipments_status_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_order_code_index');
            $table->dropIndex('orders_status_index');
            $table->dropIndex('orders_shipping_method_index');
            $table->dropIndex('orders_created_at_index');
        });
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex('order_items_order_id_index');
        });
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_order_id_index');
            $table->dropIndex('payments_status_index');
        });
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropIndex('shipments_order_id_index');
            $table->dropIndex('shipments_status_index');
        });
    }
};
