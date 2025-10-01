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
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (!Schema::hasColumn('products', 'stock_bs_kg')) {
                    $table->decimal('stock_bs_kg', 10, 2)->default(0)->after('stock');
                }

                if (!Schema::hasColumn('products', 'stock_fs_kg')) {
                    $table->decimal('stock_fs_kg', 10, 2)->default(0)->after('stock_bs_kg');
                }

                // Optional columns guards (kept for safety, though already exist in current schema)
                if (!Schema::hasColumn('products', 'sku')) {
                    $table->string('sku')->nullable()->after('slug');
                }
                if (!Schema::hasColumn('products', 'description')) {
                    $table->text('description')->nullable()->after('status');
                }
                if (!Schema::hasColumn('products', 'image_path')) {
                    $table->string('image_path')->nullable()->after('description');
                }

                // NOTE: Changing default of existing 'price' column requires doctrine/dbal.
                // To keep migrations non-destructive, we avoid altering column defaults here.
                // The model ensures safe defaults on create.
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (Schema::hasColumn('products', 'stock_bs_kg')) {
                    $table->dropColumn('stock_bs_kg');
                }
                if (Schema::hasColumn('products', 'stock_fs_kg')) {
                    $table->dropColumn('stock_fs_kg');
                }
            });
        }
    }
};