<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Changes price_per_unit from decimal(12,2) to unsigned bigInteger
     * for Indonesian Rupiah (whole number currency).
     */
    public function up(): void
    {
        Schema::table('seed_lots', function (Blueprint $table) {
            $table->unsignedBigInteger('price_per_unit')->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seed_lots', function (Blueprint $table) {
            $table->decimal('price_per_unit', 12, 2)->change();
        });
    }
};
