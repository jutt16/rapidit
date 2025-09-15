<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReviewsTable extends Migration
{
    public function up()
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id');
            $table->unsignedBigInteger('user_id')->nullable();    // reviewer (if user)
            $table->unsignedBigInteger('partner_id')->nullable(); // reviewer (if partner)
            $table->enum('reviewer_type', ['user', 'partner']);
            $table->tinyInteger('rating')->nullable();
            $table->text('comment')->nullable();
            $table->string('status')->default('approved');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('reviews');
    }
}
