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
            // Add description column if it doesn't exist
            if (!Schema::hasColumn('seed_lots', 'description')) {
                $table->text('description')->nullable()->after('is_sellable');
            }
        });

        // Migrate data from notes to description if notes column exists
        if (Schema::hasColumn('seed_lots', 'notes')) {
            DB::statement('UPDATE seed_lots SET description = notes WHERE notes IS NOT NULL AND (description IS NULL OR description = "")');
        }

        Schema::table('seed_lots', function (Blueprint $table) {
            // Remove metadata columns if they exist
            if (Schema::hasColumn('seed_lots', 'notes_last_changed_by')) {
                $table->dropForeign(['notes_last_changed_by']);
            }
            
            $columnsToRemove = ['notes', 'notes_last_changed_at', 'notes_last_changed_by'];
            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('seed_lots', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentionally no-op to avoid reintroducing legacy columns in rollback
        // Final schema keeps 'description' and removes 'notes' and its metadata
        return;
    }
};
