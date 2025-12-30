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
        if (Schema::hasTable('variety_images')) {
            Schema::table('variety_images', function (Blueprint $table) {
                if (!Schema::hasColumn('variety_images', 'deleted_at')) {
                    $table->softDeletes();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('variety_images', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
