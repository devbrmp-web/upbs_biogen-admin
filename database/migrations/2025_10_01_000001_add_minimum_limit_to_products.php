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
        if (Schema::hasTable('products') && !Schema::hasColumn('products', 'minimum_limit')) {
            Schema::table('products', function (Blueprint $table) {
                $table->decimal('minimum_limit', 10, 2)->default(0)->after('stock');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('products') && Schema::hasColumn('products', 'minimum_limit')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('minimum_limit');
            });
        }
    }
};