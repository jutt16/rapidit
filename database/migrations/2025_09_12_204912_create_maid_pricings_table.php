<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMaidPricingsTable extends Migration
{
    public function up()
    {
        Schema::create('maid_pricings', function (Blueprint $table) {
            $table->id();

            $table->integer('time')->unique(); // time in minutes, must be unique
            $table->decimal('price', 10, 2);   // price for that package
            $table->decimal('discount', 10, 2)->default(0); // discount percentage, default 0

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('maid_pricings');
    }
}
