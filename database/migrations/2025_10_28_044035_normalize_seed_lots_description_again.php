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
        Schema::table('seed_lots', function (Blueprint $table) {
            // Ensure description column exists
            if (!Schema::hasColumn('seed_lots', 'description')) {
                $table->text('description')->nullable();
            }
        });

        // Migrate data from notes to description if notes column exists
        if (Schema::hasColumn('seed_lots', 'notes')) {
            DB::statement("
                UPDATE seed_lots 
                SET description = COALESCE(notes, description) 
                WHERE description IS NULL AND notes IS NOT NULL
            ");
            
            // Drop the notes-related columns
            Schema::table('seed_lots', function (Blueprint $table) {
                $table->dropColumn(['notes', 'notes_last_changed_at', 'notes_last_changed_by']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op rollback to prevent reintroducing legacy columns
        return;
    }
};
