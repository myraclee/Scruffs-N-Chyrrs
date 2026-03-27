<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('customer_orders', function (Blueprint $table) {
            $table->id();
            
            // Foreign keys
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');
            
            $table->foreignId('product_id')
                ->constrained('products')
                ->onDelete('restrict');
            
            $table->foreignId('order_template_id')
                ->constrained('order_templates')
                ->onDelete('restrict');
            
            $table->foreignId('rush_fee_id')
                ->nullable()
                ->constrained('rush_fees')
                ->onDelete('set null');
            
            // Order configuration
            $table->json('selected_options'); // Array of selected option IDs or values
            $table->integer('quantity');
            $table->string('special_instructions')->nullable();
            
            // Pricing breakdown
            $table->decimal('base_price', 10, 2); // Price before discounts/fees
            $table->decimal('discount_amount', 10, 2)->default(0); // Bulk discount
            $table->decimal('rush_fee_amount', 10, 2)->default(0); // Rush fee cost
            $table->decimal('layout_fee_amount', 10, 2)->default(0); // Layout fee if applicable
            $table->decimal('total_price', 10, 2); // Final total
            
            // Order status
            $table->enum('status', ['pending', 'confirmed', 'processing', 'shipped', 'completed', 'cancelled'])
                ->default('pending');
            
            // Timestamps
            $table->timestamps();
            
            // Indexes for performance
            $table->index('user_id');
            $table->index('product_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_orders');
    }
};
