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
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->renameColumn('model_type', 'table_name');
            $table->renameColumn('model_id', 'record_id');
            $table->renameColumn('old_values', 'old_data');
            $table->renameColumn('new_values', 'new_data');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->renameColumn('table_name', 'model_type');
            $table->renameColumn('record_id', 'model_id');
            $table->renameColumn('old_data', 'old_values');
            $table->renameColumn('new_data', 'new_values');
        });
    }
};
