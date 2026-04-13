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
        if (! Schema::hasTable('customer_order_groups')) {
            return;
        }

        if (! Schema::hasColumn('customer_order_groups', 'payment_proof')) {
            return;
        }

        if (! Schema::hasColumn('customer_order_groups', 'payment_proof_path')) {
            return;
        }

        $legacyProofCount = DB::table('customer_order_groups')
            ->whereNotNull('payment_proof')
            ->whereRaw("TRIM(payment_proof) <> ''")
            ->count();

        if ($legacyProofCount > 0) {
            return;
        }

        Schema::table('customer_order_groups', function (Blueprint $table): void {
            $table->dropColumn('payment_proof');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('customer_order_groups')) {
            return;
        }

        if (Schema::hasColumn('customer_order_groups', 'payment_proof')) {
            return;
        }

        Schema::table('customer_order_groups', function (Blueprint $table): void {
            $table->string('payment_proof')->nullable();
        });
    }
};