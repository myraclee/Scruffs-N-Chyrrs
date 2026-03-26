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
        Schema::create('order_template_layout_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_template_id')->constrained('order_templates')->onDelete('cascade');
            $table->decimal('fee_amount', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_template_layout_fees');
    }
};
