<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds universal agricultural metadata columns to the varieties table.
     * Field names use English nomenclature for cross-commodity compatibility.
     */
    public function up(): void
    {
        Schema::table('varieties', function (Blueprint $table) {
            // --- Regulatory Identity ---
            $table->string('decree_number')->nullable()->after('description');   // Nomor SK/Keputusan pelepasan
            $table->string('decree_date')->nullable()->after('decree_number');   // Tanggal keputusan pelepasan

            // --- Origin & Morphology ---
            $table->string('origin')->nullable()->after('decree_date');          // Asal / Parentage cross
            $table->string('planting_age')->nullable()->after('origin');         // Umur tanaman (days or range)

            // --- Yield Data ---
            $table->string('yield_potential')->nullable()->after('planting_age');  // Potensi hasil (e.g. "9.0 ton/ha GKG")
            $table->string('average_yield')->nullable()->after('yield_potential'); // Rata-rata hasil

            // --- Quality & Trait ---
            $table->string('primary_trait')->nullable()->after('average_yield');   // Sifat utama: Tekstur nasi / Ukuran biji / etc.

            // --- Resistance ---
            $table->text('pest_resistance')->nullable()->after('primary_trait');     // Ketahanan hama
            $table->text('disease_resistance')->nullable()->after('pest_resistance'); // Ketahanan penyakit

            // --- Summary ---
            $table->text('description_summary')->nullable()->after('disease_resistance'); // Keunggulan + Anjuran tanam (ringkasan)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('varieties', function (Blueprint $table) {
            $table->dropColumn([
                'decree_number',
                'decree_date',
                'origin',
                'planting_age',
                'yield_potential',
                'average_yield',
                'primary_trait',
                'pest_resistance',
                'disease_resistance',
                'description_summary',
            ]);
        });
    }
};
