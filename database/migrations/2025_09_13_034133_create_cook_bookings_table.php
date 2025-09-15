<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCookBookingsTable extends Migration
{
    public function up()
    {
        Schema::create('cook_bookings', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('booking_id')->unique(); // One cook booking per booking

            $table->integer('no_of_people');
            $table->enum('food_type1', ['veg', 'non-veg', 'both']); // main type
            $table->string('food_type2'); // e.g., South Indian, North Indian, etc.
            $table->integer('no_of_dishes'); // number of dishes (1,2,3,...)

            $table->timestamps();

            // Relation with bookings
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('cook_bookings');
    }
}
