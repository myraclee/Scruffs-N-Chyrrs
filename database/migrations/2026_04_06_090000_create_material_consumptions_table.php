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
        Schema::create('material_consumptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained('materials')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('order_template_option_type_id')
                ->nullable()
                ->constrained('order_template_option_types')
                ->nullOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();

            $table->index('material_id');
            $table->index('product_id');
            $table->index('order_template_option_type_id');
            $table->unique(
                ['material_id', 'product_id', 'order_template_option_type_id'],
                'material_consumptions_unique',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_consumptions');
    }
};
