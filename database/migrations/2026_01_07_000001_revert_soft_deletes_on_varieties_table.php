<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('varieties') && Schema::hasColumn('varieties', 'deleted_at')) {
            Schema::table('varieties', function (Blueprint $table) {
                $table->dropColumn('deleted_at');
            });
        }

        if (Schema::hasTable('seed_lots')) {
            Schema::table('seed_lots', function (Blueprint $table) {
                $table->dropForeign(['variety_id']);
            });

            Schema::table('seed_lots', function (Blueprint $table) {
                $table->foreign('variety_id')->references('id')->on('varieties')->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('varieties') && ! Schema::hasColumn('varieties', 'deleted_at')) {
            Schema::table('varieties', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        if (Schema::hasTable('seed_lots')) {
            Schema::table('seed_lots', function (Blueprint $table) {
                $table->dropForeign(['variety_id']);
            });

            Schema::table('seed_lots', function (Blueprint $table) {
                $table->foreign('variety_id')->references('id')->on('varieties')->cascadeOnDelete();
            });
        }
    }
};
