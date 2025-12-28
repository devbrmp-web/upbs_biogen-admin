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
        Schema::table('shipments', function (Blueprint $table) {
            // Change courier_name to string to allow 'Ambil di Tempat' and other couriers
            $table->string('courier_name')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            // Revert to enum if possible, but data might be lost/invalid if not handled
            // For safety, we keep it as string or try to revert to enum with mapped values
            // $table->enum('courier_name', ['Pos Indonesia', 'Indah Cargo'])->nullable()->change();
        });
    }
};
