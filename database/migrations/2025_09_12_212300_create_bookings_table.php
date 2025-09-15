<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBookingsTable extends Migration
{
    public function up()
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('service_id');
            $table->unsignedBigInteger('address_id');
            $table->date('schedule_date');
            $table->string('schedule_time'); // e.g., "15:00-16:00"
            $table->enum('payment_method', ['cod', 'razorpay']); // cod, razorpay, etc.
            $table->decimal('amount', 10, 2);
            $table->decimal('tax', 10, 2);
            $table->decimal('total_amount', 10, 2);
            $table->integer('service_time')->nullable(); // only for maid
            $table->timestamps();

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');
            $table->foreign('address_id')->references('id')->on('user_addresses')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('bookings');
    }
}
