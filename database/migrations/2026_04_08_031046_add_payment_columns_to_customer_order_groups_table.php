<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('customer_order_groups', function (Blueprint $table) {
            $table->string('payment_status')->default('Awaiting Payment')->after('status');
            $table->string('payment_proof')->nullable()->after('payment_status');
        });
    }

    public function down()
    {
        Schema::table('customer_order_groups', function (Blueprint $table) {
            $table->dropColumn(['payment_status', 'payment_proof']);
        });
    }
};
