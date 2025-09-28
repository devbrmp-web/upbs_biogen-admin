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
        Schema::table('users', function (Blueprint $table) {
            // Cek apakah kolom role_id sudah ada
            if (!Schema::hasColumn('users', 'role_id')) {
                $table->unsignedBigInteger('role_id')->nullable()->after('password_hash');
            }
            
            // Tambahkan foreign key
            if (!Schema::hasColumn('users', 'role_id_foreign')) {
                $table->foreign('role_id')
                    ->references('id')
                    ->on('roles')
                    ->nullOnDelete()
                    ->cascadeOnUpdate();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop foreign key jika ada
            if (Schema::hasColumn('users', 'role_id')) {
                $table->dropForeign(['role_id']);
            }
            
            // Drop kolom hanya jika migrasi ini yang menambahkannya
            if (Schema::getConnection()->getDoctrineSchemaManager()->listTableDetails('users')->hasColumn('role_id') &&
                !Schema::hasColumn('users', 'role_id_foreign')) {
                $table->dropColumn('role_id');
            }
        });
    }
};
