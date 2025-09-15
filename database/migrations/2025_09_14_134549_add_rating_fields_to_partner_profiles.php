<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRatingFieldsToPartnerProfiles extends Migration
{
    public function up()
    {
        Schema::table('partner_profiles', function (Blueprint $table) {
            $table->decimal('average_rating', 3, 2)->default(0.00)->after('years_of_experience');
            $table->unsignedInteger('reviews_count')->default(0)->after('average_rating');
        });
    }

    public function down()
    {
        Schema::table('partner_profiles', function (Blueprint $table) {
            $table->dropColumn(['average_rating', 'reviews_count']);
        });
    }
}
