<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $hasOldIndex = collect(DB::select("SHOW INDEX FROM `reviews` WHERE Key_name = 'reviews_booking_id_unique'"))->isNotEmpty();
            if ($hasOldIndex) {
                $table->dropUnique('reviews_booking_id_unique');
            }
            $table->unique(['booking_id', 'reviewer_type'], 'reviews_booking_id_reviewer_type_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $hasNewIndex = collect(DB::select("SHOW INDEX FROM `reviews` WHERE Key_name = 'reviews_booking_id_reviewer_type_unique'"))->isNotEmpty();
            if ($hasNewIndex) {
                $table->dropUnique('reviews_booking_id_reviewer_type_unique');
            }
            $table->unique('booking_id', 'reviews_booking_id_unique');
        });
    }
};
