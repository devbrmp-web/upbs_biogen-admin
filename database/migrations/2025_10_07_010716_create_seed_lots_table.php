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
        Schema::create('seed_lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variety_id')->constrained('varieties')->cascadeOnDelete();
            $table->foreignId('seed_class_id')->constrained('seed_classes')->cascadeOnDelete();
            $table->string('lot_code')->unique(); // Kode batch unik
            $table->year('production_year'); // Tahun produksi
            $table->decimal('quantity', 12, 3); // Jumlah stok dengan 3 desimal
            $table->string('unit', 20); // kg, botol, malai, dll
            $table->decimal('price_per_unit', 12, 2); // Harga per unit
            $table->boolean('is_sellable')->default(true); // Apakah bisa dijual
            $table->text('description')->nullable(); // Optional detailed description
            $table->timestamps();
            
            // Index untuk performa query
            $table->index(['variety_id', 'seed_class_id']);
            $table->index(['is_sellable', 'quantity']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seed_lots');
    }
};
