<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customer_order_groups', function (Blueprint $table) {
            $table->json('inventory_material_requirements')->nullable()->after('total_price');
            $table->timestamp('inventory_deducted_at')->nullable()->after('inventory_material_requirements');
            $table->timestamp('inventory_restored_at')->nullable()->after('inventory_deducted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_order_groups', function (Blueprint $table) {
            $table->dropColumn([
                'inventory_material_requirements',
                'inventory_deducted_at',
                'inventory_restored_at',
            ]);
        });
    }
};
