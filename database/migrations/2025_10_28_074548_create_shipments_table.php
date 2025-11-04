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
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            
            // Shipping method (from order, but stored here for tracking)
            $table->enum('shipping_method', ['pickup', 'delivery']);
            
            // For pickup method
            $table->timestamp('ready_for_pickup_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->string('pickup_person_name', 100)->nullable(); // Who picked up
            $table->string('pickup_person_id', 50)->nullable(); // ID verification
            
            // For delivery method (call center coordination)
            $table->string('courier_name', 50)->nullable(); // JNE, TIKI, etc.
            $table->string('courier_service', 50)->nullable(); // REG, YES, etc.
            $table->string('tracking_number', 100)->nullable();
            $table->decimal('shipping_cost', 12, 2)->default(0);
            
            // Call center coordination
            $table->text('call_center_notes')->nullable(); // Instructions from call center
            $table->string('call_center_contact', 100)->nullable(); // Contact person
            $table->timestamp('call_center_contacted_at')->nullable();
            
            // Shipment status and timing
            $table->enum('status', [
                'pending', // Waiting for processing
                'ready_for_pickup', // For pickup method
                'awaiting_call_center', // For delivery method
                'call_center_contacted', // Customer contacted call center
                'shipped', // Package sent (delivery) or ready (pickup)
                'delivered', // Completed
                'failed' // Delivery failed or pickup expired
            ])->default('pending');
            
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            
            // Admin handling
            $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('admin_notes')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['order_id']);
            $table->index(['status', 'created_at']);
            $table->index(['tracking_number']);
            $table->index(['shipping_method', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
