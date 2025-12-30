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
            if (! Schema::hasColumn('orders', 'payment_type')) {
                $table->string('payment_type', 50)->nullable()->after('pnbp_receipt_no');
            }
            if (! Schema::hasColumn('orders', 'transaction_id')) {
                $table->string('transaction_id')->nullable()->after('payment_type');
            }
            if (! Schema::hasColumn('orders', 'transaction_status')) {
                $table->string('transaction_status', 50)->nullable()->after('transaction_id');
            }
            if (! Schema::hasColumn('orders', 'fraud_status')) {
                $table->string('fraud_status', 50)->nullable()->after('transaction_status');
            }
            if (! Schema::hasColumn('orders', 'gross_amount')) {
                $table->decimal('gross_amount', 12, 2)->nullable()->after('fraud_status');
            }
            if (! Schema::hasColumn('orders', 'settlement_time')) {
                $table->timestamp('settlement_time')->nullable()->after('paid_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'payment_type',
                'transaction_id',
                'transaction_status',
                'fraud_status',
                'gross_amount',
                'settlement_time',
            ]);
        });
    }
};
