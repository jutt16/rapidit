<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCookPricingsTable extends Migration
{
    public function up()
    {
        Schema::create('cook_pricings', function (Blueprint $table) {
            $table->id();

            $table->decimal('base_price', 10, 2)->default(150); // BP for 1 person, up to 2 dishes
            $table->decimal('additional_dish_charge', 10, 2)->default(50); // AD per extra dish above 2
            $table->decimal('additional_person_percentage', 5, 2)->default(40); // AP % of (BP + AD)

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('cook_pricings');
    }
}
