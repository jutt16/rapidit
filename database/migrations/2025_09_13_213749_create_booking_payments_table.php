<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBookingPaymentsTable extends Migration
{
    public function up()
    {
        Schema::create('booking_payments', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('booking_id')->index();

            // Payment method
            $table->enum('payment_method', ['cod', 'razorpay'])->default('cod');

            // Razorpay link identifiers
            $table->string('razorpay_link_id')->nullable()->index();
            $table->string('razorpay_short_url')->nullable();
            $table->string('razorpay_link_status')->nullable(); // created, active, paid, expired, cancelled

            // Razorpay payment fields (filled on callback/webhook)
            $table->string('razorpay_payment_id')->nullable()->index();
            $table->string('razorpay_signature')->nullable();
            $table->string('razorpay_payment_link_reference_id')->nullable()->index();

            $table->decimal('amount', 10, 2)->default(0.00); // rupees
            $table->enum('status', ['pending','paid','failed','expired','cancelled'])->default('pending');

            $table->json('meta')->nullable();

            $table->timestamps();

            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('booking_payments');
    }
}
