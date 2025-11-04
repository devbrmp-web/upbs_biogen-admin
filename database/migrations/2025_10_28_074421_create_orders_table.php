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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_code', 20)->unique(); // Unique order identifier for tracking
            
            // Guest customer information (no user accounts)
            $table->string('customer_name', 100);
            $table->text('customer_address');
            $table->string('customer_phone', 20);
            $table->string('customer_email', 100)->nullable(); // Optional but recommended
            
            // Order status workflow
            $table->enum('status', [
                'awaiting_payment',
                'paid', 
                'processing',
                'shipped',
                'completed',
                'cancelled'
            ])->default('awaiting_payment');
            
            // Shipping method selection - SKPL-WUB-PR-023
            $table->enum('shipping_method', [
                'pickup', // Ambil di Kantor (default, no charge)
                'delivery' // Pengiriman (via call center)
            ])->default('pickup');
            
            // Pricing snapshots (preserve at order time)
            $table->decimal('subtotal', 12, 2); // Items total
            $table->decimal('shipping_cost', 12, 2)->default(0); // Always 0 for pickup
            $table->decimal('total_amount', 12, 2); // Final amount
            
            // Payment information - SKPL-WUB-PR-022
            $table->string('pnbp_receipt_no', 50)->nullable(); // PNBP receipt number
            $table->timestamp('paid_at')->nullable();
            
            // Shipping information - SKPL-WUB-PR-023
            $table->string('courier_name', 50)->nullable(); // For delivery method
            $table->string('courier_service', 50)->nullable();
            $table->string('tracking_number', 100)->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            
            // Additional metadata
            $table->json('notes')->nullable(); // Admin notes, special instructions
            $table->timestamp('payment_deadline')->nullable(); // Payment time limit
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['status', 'created_at']);
            $table->index(['order_code']);
            $table->index(['customer_phone']); // For order tracking
            $table->index(['customer_email']); // For order tracking (if provided)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
