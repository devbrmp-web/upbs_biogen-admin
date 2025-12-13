<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Standardizes the unit 'botol' (legacy Indonesian) to 'bottle' (English standard)
     * for consistency across the application.
     */
    public function up(): void
    {
        // Update all seed_lots with unit 'botol' to 'bottle'
        $updated = DB::table('seed_lots')
            ->where('unit', 'botol')
            ->update(['unit' => 'bottle']);
        
        if ($updated > 0) {
            // Log how many records were updated (useful for auditing)
            logger()->info("Migration: Updated {$updated} seed_lots from unit 'botol' to 'bottle'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert 'bottle' back to 'botol' if migration is rolled back
        DB::table('seed_lots')
            ->where('unit', 'bottle')
            ->update(['unit' => 'botol']);
    }
};
