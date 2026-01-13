<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Removes the redundant 'price' column from varieties table.
     * Price is now managed at the SeedLot level via 'price_per_unit'.
     */
    public function up(): void
    {
        if (! env('UPBS_REMOVE_VARIETY_PRICE', false)) {
            return;
        }

        if (! Schema::hasColumn('varieties', 'price')) {
            return;
        }

        Schema::table('varieties', function (Blueprint $table) {
            $table->dropColumn('price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('varieties', function (Blueprint $table) {
            $table->unsignedBigInteger('price')->default(0)->after('description');
        });
    }
};
