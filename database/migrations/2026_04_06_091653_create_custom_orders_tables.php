<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');

        // 1. Create the Main Orders Table
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('general_gdrive_link')->nullable();
            $table->decimal('rush_fee', 10, 2)->default(0);
            $table->decimal('grand_total', 10, 2)->default(0);
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        // 2. Create the Order Items Table (The Cart Rows)
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('category')->nullable();
            $table->string('type')->nullable();
            $table->string('lamination')->nullable();
            $table->integer('quantity')->default(1);
            $table->string('design_name_link')->nullable();
            $table->decimal('layout_fee', 10, 2)->default(0);
            $table->decimal('item_total', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};