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
            // Drop indexes that reference is_active column
            $table->dropIndex(['commodity_id', 'is_active']);
            $table->dropIndex(['status', 'is_active']);
            
            // Drop the is_active column
            $table->dropColumn('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('varieties', function (Blueprint $table) {
            // Add back the is_active column
            $table->boolean('is_active')->default(true)->after('status');
            
            // Recreate the indexes
            $table->index(['commodity_id', 'is_active']);
            $table->index(['status', 'is_active']);
        });
    }
};
