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
        // Safe no-op when final schema (no legacy notes) already applied
        if (!Schema::hasColumn('seed_lots', 'notes')) {
            return; // Skip adding legacy metadata when notes column is absent
        }
        Schema::table('seed_lots', function (Blueprint $table) {
            if (!Schema::hasColumn('seed_lots', 'notes_last_changed_at')) {
                $table->timestamp('notes_last_changed_at')->nullable()->after('notes');
            }
            if (!Schema::hasColumn('seed_lots', 'notes_last_changed_by')) {
                $table->unsignedBigInteger('notes_last_changed_by')->nullable()->after('notes_last_changed_at');
                $table->foreign('notes_last_changed_by')->references('id')->on('users')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Safe no-op if columns don't exist
        if (!Schema::hasColumn('seed_lots', 'notes_last_changed_at') && !Schema::hasColumn('seed_lots', 'notes_last_changed_by')) {
            return;
        }
        Schema::table('seed_lots', function (Blueprint $table) {
            if (Schema::hasColumn('seed_lots', 'notes_last_changed_by')) {
                // Drop FK if present (guarded)
                try { $table->dropForeign(['notes_last_changed_by']); } catch (\Throwable $e) { /* ignore if not present */ }
            }
            $table->dropColumn(array_values(array_filter([
                Schema::hasColumn('seed_lots', 'notes_last_changed_at') ? 'notes_last_changed_at' : null,
                Schema::hasColumn('seed_lots', 'notes_last_changed_by') ? 'notes_last_changed_by' : null,
            ])));
        });
    }
};
