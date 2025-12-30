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
        Schema::table('orders', function (Blueprint $table) {
            // Check if column exists, if so change it, else add it
            if (Schema::hasColumn('orders', 'signature_path')) {
                $table->text('signature_path')->nullable()->change();
            } else {
                $table->text('signature_path')->nullable()->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'signature_path')) {
                $table->string('signature_path', 255)->nullable()->change();
            }
        });
    }
};
