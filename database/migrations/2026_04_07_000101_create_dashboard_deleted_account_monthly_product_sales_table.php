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
        Schema::create('dashboard_deleted_account_monthly_product_sales', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->unsignedBigInteger('product_id');
            $table->string('product_name');
            $table->unsignedBigInteger('total_quantity')->default(0);
            $table->timestamps();

            $table->unique(['year', 'month', 'product_id'], 'dashboard_deleted_account_monthly_product_sales_unique');
            $table->index(['year', 'month'], 'dashboard_deleted_account_monthly_product_sales_year_month_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dashboard_deleted_account_monthly_product_sales');
    }
};
