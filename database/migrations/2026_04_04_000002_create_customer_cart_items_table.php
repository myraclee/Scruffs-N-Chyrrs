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
        Schema::create('customer_cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_cart_id')
                ->constrained('customer_carts')
                ->cascadeOnDelete();
            $table->foreignId('product_id')
                ->constrained('products')
                ->restrictOnDelete();
            $table->foreignId('order_template_id')
                ->constrained('order_templates')
                ->restrictOnDelete();
            $table->foreignId('rush_fee_id')
                ->nullable()
                ->constrained('rush_fees')
                ->nullOnDelete();

            $table->json('selected_options');
            $table->integer('quantity');
            $table->string('special_instructions')->nullable();

            $table->decimal('base_price', 10, 2);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('rush_fee_amount', 10, 2)->default(0);
            $table->decimal('layout_fee_amount', 10, 2)->default(0);
            $table->decimal('total_price', 10, 2);
            $table->timestamps();

            $table->index('customer_cart_id');
            $table->index('product_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_cart_items');
    }
};
