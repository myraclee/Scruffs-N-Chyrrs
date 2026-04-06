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
        Schema::create('dashboard_deleted_account_daily_metrics', function (Blueprint $table) {
            $table->date('metric_date')->primary();
            $table->decimal('total_sales', 12, 2)->default(0);
            $table->unsignedBigInteger('items_sold')->default(0);
            $table->unsignedBigInteger('total_orders')->default(0);
            $table->unsignedBigInteger('received_payment')->default(0);
            $table->unsignedBigInteger('pending_payment')->default(0);
            $table->unsignedBigInteger('canceled_orders')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dashboard_deleted_account_daily_metrics');
    }
};
