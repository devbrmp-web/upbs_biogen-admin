<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('gateway_status', 50)->nullable()->after('status');
            $table->string('fraud_status', 50)->nullable()->after('gateway_status');
            $table->string('gateway_signature', 150)->nullable()->after('signature_verification');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['gateway_status', 'fraud_status', 'gateway_signature']);
        });
    }
};

