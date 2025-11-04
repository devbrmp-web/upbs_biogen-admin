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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            
            // Action details
            $table->string('action', 50); // CREATE, UPDATE, DELETE
            $table->string('model_type', 100); // App\Models\Order, App\Models\Variety, etc.
            $table->unsignedBigInteger('model_id')->nullable(); // ID of affected record
            
            // Request context
            $table->string('route_name', 100)->nullable(); // Laravel route name
            $table->string('url', 255)->nullable(); // Full URL
            $table->string('method', 10)->nullable(); // GET, POST, PUT, DELETE
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent', 500)->nullable();
            
            // Data changes (for UPDATE actions)
            $table->json('old_values')->nullable(); // Before changes
            $table->json('new_values')->nullable(); // After changes
            
            // Additional context
            $table->text('description')->nullable(); // Human-readable description
            $table->json('metadata')->nullable(); // Additional context data
            
            // Categorization for filtering
            $table->enum('category', [
                'order_management', // Order status changes, creation, etc.
                'inventory_management', // Stock changes, variety updates
                'user_management', // Admin user changes
                'payment_processing', // Payment status changes
                'shipping_fulfillment', // Shipping status updates
                'system_configuration', // Settings changes
                'data_export', // CSV exports, reports
                'authentication' // Login/logout events
            ])->nullable();
            
            $table->timestamps();
            
            // Indexes for efficient querying
            $table->index(['user_id', 'created_at']);
            $table->index(['model_type', 'model_id']);
            $table->index(['action', 'created_at']);
            $table->index(['category', 'created_at']);
            $table->index(['ip_address', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
