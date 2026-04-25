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
        Schema::table('seed_classes', function (Blueprint $table) {
            $table->enum('stock_category', ['weight', 'unit'])->default('weight')->after('description');
            $table->string('default_unit')->default('kg')->after('stock_category');
            $table->integer('min_order_qty')->default(1)->after('default_unit');
            $table->integer('step_increment')->default(1)->after('min_order_qty');
        });

        // Seed initial data for existing classes
        if (Schema::hasTable('seed_classes')) {
            // ST (Starter)
            DB::table('seed_classes')->where('code', 'ST')->update([
                'stock_category' => 'unit',
                'default_unit' => 'bottle',
                'min_order_qty' => 1,
                'step_increment' => 1
            ]);
            
            // FS (Foundation)
            DB::table('seed_classes')->where('code', 'FS')->update([
                'stock_category' => 'weight',
                'default_unit' => 'kg',
                'min_order_qty' => 1,
                'step_increment' => 5
            ]);

            // BS (Breeder)
            DB::table('seed_classes')->where('code', 'BS')->update([
                'stock_category' => 'weight',
                'default_unit' => 'kg',
                'min_order_qty' => 1,
                'step_increment' => 1
            ]);

            // SS (Stock Seed)
            DB::table('seed_classes')->where('code', 'SS')->update([
                'stock_category' => 'weight',
                'default_unit' => 'kg',
                'min_order_qty' => 1,
                'step_increment' => 1
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seed_classes', function (Blueprint $table) {
            if (Schema::hasColumn('seed_classes', 'stock_category')) {
                $table->dropColumn('stock_category');
            }
            if (Schema::hasColumn('seed_classes', 'default_unit')) {
                $table->dropColumn('default_unit');
            }
            if (Schema::hasColumn('seed_classes', 'min_order_qty')) {
                $table->dropColumn('min_order_qty');
            }
            if (Schema::hasColumn('seed_classes', 'step_increment')) {
                $table->dropColumn('step_increment');
            }
        });
    }
};
