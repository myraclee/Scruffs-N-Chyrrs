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
        Schema::table('customer_orders', function (Blueprint $table) {
            $table->foreignId('customer_order_group_id')
                ->nullable()
                ->after('id')
                ->constrained('customer_order_groups')
                ->nullOnDelete();
        });

        DB::table('customer_orders')
            ->where('status', 'pending')
            ->update(['status' => 'waiting']);

        DB::table('customer_orders')
            ->where('status', 'confirmed')
            ->update(['status' => 'approved']);

        DB::table('customer_orders')
            ->where('status', 'processing')
            ->update(['status' => 'preparing']);

        DB::table('customer_orders')
            ->where('status', 'shipped')
            ->update(['status' => 'ready']);

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE customer_orders MODIFY status ENUM('waiting', 'approved', 'preparing', 'ready', 'completed', 'cancelled') NOT NULL DEFAULT 'waiting'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::table('customer_orders')
                ->where('status', 'waiting')
                ->update(['status' => 'pending']);

            DB::table('customer_orders')
                ->where('status', 'approved')
                ->update(['status' => 'confirmed']);

            DB::table('customer_orders')
                ->where('status', 'preparing')
                ->update(['status' => 'processing']);

            DB::table('customer_orders')
                ->where('status', 'ready')
                ->update(['status' => 'shipped']);

            DB::statement("ALTER TABLE customer_orders MODIFY status ENUM('pending', 'confirmed', 'processing', 'shipped', 'completed', 'cancelled') NOT NULL DEFAULT 'pending'");
        }

        Schema::table('customer_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_order_group_id');
        });
    }
};
