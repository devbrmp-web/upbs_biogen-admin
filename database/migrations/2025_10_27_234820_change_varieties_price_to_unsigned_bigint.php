<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, normalize existing decimal values to integers (round to nearest rupiah)
        DB::statement('UPDATE varieties SET price = ROUND(price)');
        
        // Change column type to unsignedBigInteger
        Schema::table('varieties', function (Blueprint $table) {
            $table->unsignedBigInteger('price')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to decimal(12,2) unsigned
        Schema::table('varieties', function (Blueprint $table) {
            $table->decimal('price', 12, 2)->unsigned()->change();
        });
    }
};
