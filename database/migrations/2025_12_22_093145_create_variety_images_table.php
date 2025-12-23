<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('variety_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variety_id')->constrained()->cascadeOnDelete();
            $table->string('image_path');
            $table->boolean('is_primary')->default(false);
            $table->integer('order')->default(0);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['variety_id']);
            $table->index(['variety_id', 'order']);
        });

        $driver = DB::getDriverName();
        if ($driver === 'pgsql') {
            DB::statement('CREATE UNIQUE INDEX variety_images_one_primary_per_variety ON variety_images (variety_id) WHERE is_primary = TRUE AND deleted_at IS NULL');
        } elseif ($driver === 'sqlite') {
            DB::statement('CREATE UNIQUE INDEX variety_images_one_primary_per_variety ON variety_images (variety_id) WHERE is_primary = 1 AND deleted_at IS NULL');
        } elseif ($driver === 'mysql') {
            DB::statement('ALTER TABLE variety_images ADD COLUMN primary_unique TINYINT(1) GENERATED ALWAYS AS (CASE WHEN is_primary = 1 AND deleted_at IS NULL THEN 1 ELSE NULL END) STORED');
            DB::statement('CREATE UNIQUE INDEX variety_images_one_primary_per_variety ON variety_images (variety_id, primary_unique)');
        }

        $now = now();
        $existing = DB::table('varieties')
            ->select('id', 'image_path')
            ->whereNotNull('image_path')
            ->get();

        if ($existing->count() > 0) {
            $rows = $existing->map(function ($row) use ($now) {
                return [
                    'variety_id' => $row->id,
                    'image_path' => $row->image_path,
                    'is_primary' => true,
                    'order' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })->all();

            DB::table('variety_images')->insert($rows);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('variety_images');
    }
};
