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
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropUnique('reviews_booking_id_unique');
            $table->unique(['booking_id', 'reviewer_type'], 'reviews_booking_id_reviewer_type_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropUnique('reviews_booking_id_reviewer_type_unique');
            $table->unique('booking_id', 'reviews_booking_id_unique');
        });
    }
};
