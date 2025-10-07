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
        Schema::create('seed_classes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique(); // BS, FS, NS
            $table->string('name'); // Benih Sebar, Benih Pokok, Benih Sumber
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seed_classes');
    }
};
