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
        // Ensure description column exists
        if (!Schema::hasColumn('seed_lots', 'description')) {
            Schema::table('seed_lots', function (Blueprint $table) {
                $table->text('description')->nullable()->after('is_sellable');
            });
        }

        // Migrate any remaining notes data to description if notes column still exists
        if (Schema::hasColumn('seed_lots', 'notes')) {
            DB::statement('UPDATE seed_lots SET description = COALESCE(description, notes) WHERE notes IS NOT NULL');
        }

        // Clean up any remaining notes-related columns
        Schema::table('seed_lots', function (Blueprint $table) {
            // Drop foreign key constraint first if it exists
            if (Schema::hasColumn('seed_lots', 'notes_last_changed_by')) {
                try {
                    $table->dropForeign(['notes_last_changed_by']);
                } catch (Exception $e) {
                    // Foreign key might not exist, continue
                }
            }
            
            // Drop columns if they exist
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
        // No-op rollback: do not recreate legacy columns
        return;
    }
};
