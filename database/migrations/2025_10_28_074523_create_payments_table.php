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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            
            // Payment gateway information
            $table->string('payment_method', 50); // VA, QRIS, transfer, etc.
            $table->string('gateway_transaction_id', 100)->nullable(); // From payment gateway
            $table->string('gateway_reference', 100)->nullable(); // Gateway reference number
            
            // PNBP information - SKPL-WUB-PR-022
            $table->string('pnbp_receipt_no', 50)->nullable(); // PNBP receipt number
            $table->decimal('amount', 12, 2); // Payment amount
            
            // Payment status and timing
            $table->enum('status', [
                'pending',
                'paid',
                'failed',
                'cancelled',
                'expired'
            ])->default('pending');
            
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expires_at')->nullable(); // Payment deadline
            
            // Gateway webhook data
            $table->json('gateway_response')->nullable(); // Store webhook payload
            $table->string('signature_verification', 100)->nullable(); // For security
            
            // Audit trail
            $table->ipAddress('payment_ip')->nullable();
            $table->text('notes')->nullable(); // Admin notes
            
            $table->timestamps();
            
            // Indexes
            $table->index(['order_id']);
            $table->index(['status', 'created_at']);
            $table->index(['gateway_transaction_id']);
            $table->index(['pnbp_receipt_no']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
