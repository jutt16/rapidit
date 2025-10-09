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
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('banking_detail_id')->constrained('banking_details')->onDelete('cascade');
            $table->decimal('amount', 16, 2);
            $table->decimal('fee', 16, 2)->default(0);
            $table->string('currency', 10)->default('INR');
            $table->string('status')->default('pending'); // pending, approved, processing, completed, rejected, cancelled
            $table->string('reference')->nullable(); // provider tx id
            $table->text('admin_note')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withdrawals');
    }
};
