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
        Schema::create('partner_service_preferences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('partner_profile_id')->index();
            $table->unsignedBigInteger('service_id')->index();
            $table->boolean('own_tools_available')->default(false);
            $table->timestamps();

            $table->foreign('partner_profile_id')->references('id')->on('partner_profiles')->onDelete('cascade');
            $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_service_preferences');
    }
};
