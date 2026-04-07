<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('rush_fees', function (Blueprint $table) {
            $table->decimal('max_price', 10, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('rush_fees')
            ->whereNull('max_price')
            ->update(['max_price' => DB::raw('min_price')]);

        Schema::table('rush_fees', function (Blueprint $table) {
            $table->decimal('max_price', 10, 2)->nullable(false)->change();
        });
    }
};
