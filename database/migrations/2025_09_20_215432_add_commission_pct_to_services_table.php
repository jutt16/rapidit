<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            // Commission as percentage, supports values 0.00 - 999.99 (we'll validate 0-100 in forms)
            $table->decimal('commission_pct', 5, 2)->default(0)->after('tax')
                ->comment('Platform commission percentage (0-100)');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('commission_pct');
        });
    }
};
