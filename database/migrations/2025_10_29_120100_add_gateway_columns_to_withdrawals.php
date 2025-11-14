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
        Schema::table('withdrawals', function (Blueprint $table) {
            $table->string('gateway')->default('razorpay')->after('status');
            $table->string('gateway_payout_id')->nullable()->after('gateway');
            $table->string('gateway_status')->nullable()->after('gateway_payout_id');
            $table->string('failure_reason')->nullable()->after('gateway_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('withdrawals', function (Blueprint $table) {
            $table->dropColumn(['gateway', 'gateway_payout_id', 'gateway_status', 'failure_reason']);
        });
    }
};


