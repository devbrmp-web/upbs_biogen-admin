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
        Schema::create('varieties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commodity_id')->constrained('commodities')->cascadeOnDelete();
            $table->string('name'); // Nama varietas
            $table->string('slug')->unique(); // URL-friendly name
            $table->string('sku')->unique(); // Stock Keeping Unit
            $table->text('description')->nullable(); // Deskripsi varietas
            $table->decimal('price', 12, 2)->default(0); // Harga dasar per unit
            $table->integer('stock')->default(0); // Stok total
            $table->decimal('stock_bs_kg', 12, 3)->default(0); // Stok BS dalam kg
            $table->decimal('stock_fs_kg', 12, 3)->default(0); // Stok FS dalam kg
            $table->integer('minimum_limit')->default(0); // Batas minimum stok
            $table->enum('status', ['available', 'out_of_stock', 'discontinued'])->default('available');
            $table->boolean('is_active')->default(true); // Status aktif
            $table->string('image_path')->nullable(); // Path gambar varietas
            $table->timestamps();

            // Index untuk performa query
            $table->index(['commodity_id', 'is_active']);
            $table->index(['status', 'is_active']);
            $table->index(['sku']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('varieties');
    }
};
