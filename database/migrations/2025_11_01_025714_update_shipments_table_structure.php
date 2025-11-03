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
        Schema::table('shipments', function (Blueprint $table) {
            // Drop foreign key constraint first
            if (Schema::hasColumn('shipments', 'processed_by')) {
                $table->dropForeign(['processed_by']);
            }
        });
        
        // Update existing data to match new enum values
        DB::table('shipments')->where('status', 'awaiting_call_center')->update(['status' => 'pending']);
        DB::table('shipments')->where('status', 'call_center_contacted')->update(['status' => 'ready_for_pickup']);
        DB::table('shipments')->where('status', 'failed')->update(['status' => 'cancelled']);
        
        // Update courier_name data to match new enum values
        DB::table('shipments')->whereNotIn('courier_name', ['Pos Indonesia', 'Indah Cargo'])->update(['courier_name' => 'Pos Indonesia']);
        
        Schema::table('shipments', function (Blueprint $table) {
            // Remove irrelevant columns
            $columnsToRemove = [
                'pickup_person_name',
                'pickup_person_id', 
                'courier_service',
                'shipping_cost',
                'call_center_notes',
                'call_center_contact',
                'call_center_contacted_at',
                'picked_up_at',
                'processed_by',
                'admin_notes'
            ];
            
            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('shipments', $column)) {
                    $table->dropColumn($column);
                }
            }
            
            // Update status enum to simplified values
            $table->enum('status', [
                'pending',
                'ready_for_pickup', 
                'shipped',
                'delivered',
                'cancelled'
            ])->default('pending')->change();
            
            // Update courier_name to only allow Pos Indonesia and Indah Cargo
            $table->enum('courier_name', ['Pos Indonesia', 'Indah Cargo'])->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            // Add back removed columns
            $table->string('pickup_person_name', 100)->nullable();
            $table->string('pickup_person_id', 50)->nullable();
            $table->string('courier_service', 50)->nullable();
            $table->decimal('shipping_cost', 12, 2)->default(0);
            $table->text('call_center_notes')->nullable();
            $table->string('call_center_contact', 100)->nullable();
            $table->timestamp('call_center_contacted_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('admin_notes')->nullable();
            
            // Revert status enum
            $table->enum('status', [
                'pending',
                'ready_for_pickup',
                'awaiting_call_center',
                'call_center_contacted',
                'shipped',
                'delivered',
                'failed'
            ])->default('pending')->change();
            
            // Revert courier_name to string
            $table->string('courier_name', 50)->nullable()->change();
        });
    }
};
