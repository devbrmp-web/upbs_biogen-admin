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
        Schema::create('commodities', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nama komoditas (e.g., Padi, Jagung, Kedelai)
            $table->string('slug')->unique(); // URL-friendly name
            $table->text('description')->nullable(); // Deskripsi komoditas
            $table->string('image_path')->nullable(); // Path gambar komoditas
            $table->boolean('is_active')->default(true); // Status aktif
            $table->timestamps();
            
            // Index untuk performa query
            $table->index(['is_active', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commodities');
    }
};