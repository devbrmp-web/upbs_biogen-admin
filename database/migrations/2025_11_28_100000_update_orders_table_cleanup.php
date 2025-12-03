<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'courier_name')) {
                $col = $table->string('courier_name', 50)->nullable();
                if (config('database.default') !== 'sqlite') {
                    $col->collation('utf8mb4_unicode_ci');
                }
                $col->change();
            }

            if (Schema::hasColumn('orders', 'shipping_cost')) {
                $table->dropColumn('shipping_cost');
            }
            if (Schema::hasColumn('orders', 'shipped_at')) {
                $table->dropColumn('shipped_at');
            }
            if (Schema::hasColumn('orders', 'courier_service')) {
                $table->dropColumn('courier_service');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'shipping_cost')) {
                $table->decimal('shipping_cost', 12, 2)->default(0);
            }
            if (!Schema::hasColumn('orders', 'shipped_at')) {
                $table->timestamp('shipped_at')->nullable();
            }
            if (!Schema::hasColumn('orders', 'courier_service')) {
                $table->string('courier_service', 50)->nullable();
            }

            if (Schema::hasColumn('orders', 'courier_name')) {
                $col = $table->string('courier_name', 50)->nullable();
                if (config('database.default') !== 'sqlite') {
                    $col->collation('utf8mb4_unicode_ci');
                }
                $col->change();
            }
        });
    }
};
