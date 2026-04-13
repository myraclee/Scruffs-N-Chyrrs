<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('materials')) {
            return;
        }

        $columnWasAdded = false;

        if (! Schema::hasColumn('materials', 'max_units')) {
            Schema::table('materials', function (Blueprint $table) {
                $table->unsignedInteger('max_units')
                    ->default(1)
                    ->after('units');
            });

            $columnWasAdded = true;
        }

        if ($columnWasAdded) {
            DB::table('materials')->update([
                'max_units' => DB::raw('CASE WHEN units > 0 THEN units ELSE 1 END'),
            ]);

            return;
        }

        DB::table('materials')
            ->whereNull('max_units')
            ->orWhere('max_units', '<', 1)
            ->update([
                'max_units' => 1,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('materials') || ! Schema::hasColumn('materials', 'max_units')) {
            return;
        }

        Schema::table('materials', function (Blueprint $table) {
            $table->dropColumn('max_units');
        });
    }
};
