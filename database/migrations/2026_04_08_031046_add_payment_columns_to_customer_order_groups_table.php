<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Intentionally left as a no-op.
        //
        // The canonical payment schema was introduced by:
        // 2026_04_08_010000_add_payment_flow_columns_to_customer_order_groups_table
        //
        // Keeping this migration as a compatibility no-op prevents duplicate-column
        // failures on environments where this file still exists in migration history.
    }

    public function down(): void
    {
        // No-op to mirror up().
    }
};
