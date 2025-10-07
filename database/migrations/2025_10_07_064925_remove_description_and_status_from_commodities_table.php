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
        Schema::table('commodities', function (Blueprint $table) {
            // Drop the composite index first if it exists
            try {
                $table->dropIndex(['is_active', 'name']);
            } catch (Exception $e) {
                // Index might not exist, continue
            }
            
            // Remove description and is_active columns
            $table->dropColumn(['description', 'is_active']);
            
            // Add new index for name only
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('commodities', function (Blueprint $table) {
            // Re-add the removed columns
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            
            // Drop the name-only index
            $table->dropIndex(['name']);
            
            // Re-add the composite index
            $table->index(['is_active', 'name']);
        });
    }
};
