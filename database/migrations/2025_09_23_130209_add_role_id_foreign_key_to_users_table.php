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
        // Pastikan kolom tersedia
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'role_id')) {
                $table->unsignedBigInteger('role_id')->nullable()->after('password_hash');
            }
        });

        // Tambahkan foreign key hanya jika belum ada
        if (Schema::hasColumn('users', 'role_id')) {
            // Check if we're using SQLite (for testing)
            $driver = DB::connection()->getDriverName();
            
            if ($driver === 'sqlite') {
                // For SQLite, just add the foreign key without checking information_schema
                try {
                    Schema::table('users', function (Blueprint $table) {
                        $table->foreign('role_id')
                            ->references('id')
                            ->on('roles')
                            ->nullOnDelete()
                            ->cascadeOnUpdate();
                    });
                } catch (\Exception $e) {
                    // Foreign key might already exist, ignore
                }
            } else {
                // For MySQL, check information_schema
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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop foreign key jika ada
            if (Schema::hasColumn('users', 'role_id')) {
                $driver = DB::connection()->getDriverName();
                
                if ($driver === 'sqlite') {
                    // For SQLite, just try to drop the foreign key
                    try {
                        $table->dropForeign(['role_id']);
                    } catch (\Exception $e) {
                        // Foreign key might not exist, ignore
                    }
                } else {
                    // For MySQL, check information_schema
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
            }
        });

        // Drop kolom hanya jika tidak ada FK yang menempel
        if (Schema::hasColumn('users', 'role_id')) {
            $driver = DB::connection()->getDriverName();
            
            if ($driver === 'sqlite') {
                // For SQLite, just drop the column
                Schema::table('users', function (Blueprint $table) {
                    $table->dropColumn('role_id');
                });
            } else {
                // For MySQL, check information_schema
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
    }
};
