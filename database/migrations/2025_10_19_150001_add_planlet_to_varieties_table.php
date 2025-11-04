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
        Schema::table('varieties', function (Blueprint $table) {
            $table->unsignedInteger('planlet')->default(0)->after('stock_fs_kg');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('varieties', function (Blueprint $table) {
            $table->dropColumn('planlet');
        });
    }
};