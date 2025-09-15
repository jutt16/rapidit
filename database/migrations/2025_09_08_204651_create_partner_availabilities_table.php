<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePartnerAvailabilitiesTable extends Migration
{
    public function up()
    {
        Schema::create('partner_availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')
                ->constrained('users')
                ->onDelete('cascade'); // partner_id references users.id
            $table->boolean('is_available')->default(true); // the toggle: true/false
            $table->string('status')->nullable(); // optional human readable status (e.g. "available", "busy")
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->json('blocked_dates')->nullable(); // stores ["2025-06-20","2025-06-25"]
            $table->timestamps();

            $table->unique('partner_id'); // one availability per partner
        });
    }

    public function down()
    {
        Schema::dropIfExists('partner_availabilities');
    }
}
