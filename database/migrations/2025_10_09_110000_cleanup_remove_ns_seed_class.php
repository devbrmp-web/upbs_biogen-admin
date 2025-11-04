<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Hapus kelas benih 'NS' (Non-Standard) beserta lot terkait (cascade)
        $nsClass = DB::table('seed_classes')->where('code', 'NS')->first();
        if ($nsClass) {
            DB::table('seed_classes')->where('id', $nsClass->id)->delete();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Kembalikan kelas 'NS' sebagai tidak aktif jika perlu rollback
        $exists = DB::table('seed_classes')->where('code', 'NS')->exists();
        if (!$exists) {
            DB::table('seed_classes')->insert([
                'code' => 'NS',
                'name' => 'Non-Standard',
                'description' => 'Legacy seed class (not used).',
                'is_active' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};