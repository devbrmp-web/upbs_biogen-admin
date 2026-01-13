<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Removes legacy stock columns from varieties table.
     * Stock is now managed at the SeedLot level via the 'quantity' column.
     */
    public function up(): void
    {
        if (! env('UPBS_REMOVE_VARIETY_LEGACY_STOCK_COLUMNS', false)) {
            return;
        }

        Schema::table('varieties', function (Blueprint $table) {
            // Drop legacy columns if they exist
            $columns = ['stock', 'stock_bs_kg', 'stock_fs_kg', 'planlet'];

            foreach ($columns as $column) {
                if (Schema::hasColumn('varieties', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('varieties', function (Blueprint $table) {
            // Restore legacy columns
            if (! Schema::hasColumn('varieties', 'stock')) {
                $table->decimal('stock', 12, 3)->default(0)->after('description');
            }
            if (! Schema::hasColumn('varieties', 'stock_bs_kg')) {
                $table->decimal('stock_bs_kg', 12, 3)->default(0)->after('stock');
            }
            if (! Schema::hasColumn('varieties', 'stock_fs_kg')) {
                $table->decimal('stock_fs_kg', 12, 3)->default(0)->after('stock_bs_kg');
            }
            if (! Schema::hasColumn('varieties', 'planlet')) {
                $table->integer('planlet')->default(0)->after('stock_fs_kg');
            }
        });
    }
};
