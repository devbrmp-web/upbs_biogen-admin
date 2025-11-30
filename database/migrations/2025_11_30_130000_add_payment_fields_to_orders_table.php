<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('gross_amount', 12, 2)->nullable()->after('total_amount');
            $table->string('payment_type', 50)->nullable()->after('pnbp_receipt_no');
            $table->string('transaction_id', 100)->nullable()->after('payment_type');
            $table->string('transaction_status', 50)->nullable()->after('transaction_id');
            $table->timestamp('settlement_time')->nullable()->after('paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['gross_amount', 'payment_type', 'transaction_id', 'transaction_status', 'settlement_time']);
        });
    }
};

