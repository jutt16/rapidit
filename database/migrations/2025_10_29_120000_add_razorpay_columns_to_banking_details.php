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
        Schema::table('banking_details', function (Blueprint $table) {
            $table->string('razorpay_contact_id')->nullable()->after('status');
            $table->string('razorpay_fund_account_id')->nullable()->after('razorpay_contact_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('banking_details', function (Blueprint $table) {
            $table->dropColumn(['razorpay_contact_id', 'razorpay_fund_account_id']);
        });
    }
};


