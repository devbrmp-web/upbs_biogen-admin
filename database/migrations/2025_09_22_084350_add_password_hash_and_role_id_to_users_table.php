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
            if (!Schema::hasColumn('users', 'password_hash')) {
                $table->string('password_hash')->nullable();
            }
            
            if (!Schema::hasColumn('users', 'role_id')) {
                $table->unsignedBigInteger('role_id')->nullable();
                // Tambahkan foreign key jika tabel roles sudah ada
                if (Schema::hasTable('roles')) {
                    $table->foreign('role_id')->references('id')->on('roles')->nullOnDelete();
                }
            }
            
            if (!Schema::hasColumn('users', 'remember_token')) {
                $table->rememberToken();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('password_hash');
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
            $table->dropColumn('remember_token');
        });
    }
};
