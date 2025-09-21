<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            // Add a decimal field for cancellation charges
            $table->decimal('cancellation_charges', 10, 2)
                ->default(0)
                ->after('commission_pct');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('cancellation_charges');
        });
    }
};
