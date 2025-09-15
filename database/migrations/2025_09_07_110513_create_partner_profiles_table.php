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
        Schema::create('partner_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index()->comment('FK to users table');
            $table->string('full_name');
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('profile_picture')->nullable();

            $table->json('languages')->nullable(); // store ["English","Hindi"]

            $table->integer('years_of_experience')->default(0);
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();

            // Documents
            $table->string('aadhar_card')->nullable();
            $table->string('pan_card')->nullable();
            $table->string('police_verification')->nullable();
            $table->string('covid_vaccination_certificate')->nullable();

            // Optional
            $table->string('selfie_with_costume')->nullable();
            $table->string('intro_video')->nullable();

            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_profiles');
    }
};
