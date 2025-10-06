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
        if (!Schema::hasTable('product_ns_batches')) {
            Schema::create('product_ns_batches', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')
                    ->constrained('products')
                    ->cascadeOnDelete()
                    ->cascadeOnUpdate();
                $table->unsignedSmallInteger('year');
                $table->decimal('quantity', 10, 2);
                $table->enum('unit', ['ikat', 'malai']);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_ns_batches');
    }
};