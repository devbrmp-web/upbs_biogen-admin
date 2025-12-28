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
            $table->dropColumn('transaction_fee');
            $table->decimal('service_fee', 12, 2)->default(0)->after('shipping_cost');
            $table->decimal('app_fee', 12, 2)->default(0)->after('service_fee');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['service_fee', 'app_fee']);
            $table->decimal('transaction_fee', 12, 2)->default(0)->after('shipping_cost');
        });
    }
};
