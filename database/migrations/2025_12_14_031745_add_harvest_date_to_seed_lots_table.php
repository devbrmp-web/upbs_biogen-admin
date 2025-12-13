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
        Schema::table('seed_lots', function (Blueprint $table) {
            $table->date('harvest_date')->nullable()->after('production_year');
        });

        // Backfilling Logic: Set harvest_date based on production_year
        DB::statement("UPDATE seed_lots SET harvest_date = CONCAT(production_year, '-01-01') WHERE harvest_date IS NULL AND production_year IS NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seed_lots', function (Blueprint $table) {
            $table->dropColumn('harvest_date');
        });
    }
};
