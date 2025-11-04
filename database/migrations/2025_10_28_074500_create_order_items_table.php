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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('variety_id')->constrained('varieties')->onDelete('restrict');
            
            // Product snapshot at order time (preserve pricing/details)
            $table->string('variety_name', 100); // Snapshot of variety name
            $table->string('variety_sku', 50); // Snapshot of SKU
            $table->decimal('unit_price', 12, 2); // Price per unit at order time
            
            // Quantity and totals
            $table->integer('quantity'); // Number of units ordered
            $table->decimal('total_price', 12, 2); // quantity * unit_price
            
            // Seed lot information (if applicable)
            $table->foreignId('seed_lot_id')->nullable()->constrained('seed_lots')->onDelete('set null');
            $table->string('seed_class', 10)->nullable(); // BS, FS, NS
            
            $table->timestamps();
            
            // Indexes
            $table->index(['order_id', 'variety_id']);
            $table->index(['variety_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
