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
        Schema::create('banking_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('bank_name')->nullable();
            $table->string('account_holder_name');
            $table->string('account_number'); // encrypted via model cast
            $table->string('ifsc')->nullable();
            $table->string('branch')->nullable();
            $table->string('currency', 10)->default('PKR');
            $table->boolean('is_default')->default(false);
            $table->string('status')->default('unverified'); // optional: unverified/verified
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banking_details');
    }
};
