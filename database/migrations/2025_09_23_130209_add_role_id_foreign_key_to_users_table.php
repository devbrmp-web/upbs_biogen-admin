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
        // Pastikan kolom tersedia
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'role_id')) {
                $table->unsignedBigInteger('role_id')->nullable()->after('password_hash');
            }
        });

        // Tambahkan foreign key hanya jika belum ada (cek via information_schema)
        if (Schema::hasColumn('users', 'role_id')) {
            $fkExists = DB::select(<<<SQL
                SELECT CONSTRAINT_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'users'
                  AND COLUMN_NAME = 'role_id'
                  AND REFERENCED_TABLE_NAME IS NOT NULL
            SQL);

            if (count($fkExists) === 0) {
                Schema::table('users', function (Blueprint $table) {
                    $table->foreign('role_id')
                        ->references('id')
                        ->on('roles')
                        ->nullOnDelete()
                        ->cascadeOnUpdate();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop foreign key jika ada (cek via information_schema)
            if (Schema::hasColumn('users', 'role_id')) {
                $fkExists = DB::select(<<<SQL
                    SELECT CONSTRAINT_NAME
                    FROM information_schema.KEY_COLUMN_USAGE
                    WHERE TABLE_SCHEMA = DATABASE()
                      AND TABLE_NAME = 'users'
                      AND COLUMN_NAME = 'role_id'
                      AND REFERENCED_TABLE_NAME IS NOT NULL
                SQL);
                if (count($fkExists) > 0) {
                    $table->dropForeign(['role_id']);
                }
            }
        });

        // Drop kolom hanya jika tidak ada FK yang menempel
        if (Schema::hasColumn('users', 'role_id')) {
            $fkExists = DB::select(<<<SQL
                SELECT CONSTRAINT_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'users'
                  AND COLUMN_NAME = 'role_id'
                  AND REFERENCED_TABLE_NAME IS NOT NULL
            SQL);
            if (count($fkExists) === 0) {
                Schema::table('users', function (Blueprint $table) {
                    $table->dropColumn('role_id');
                });
            }
        }
    }
};
