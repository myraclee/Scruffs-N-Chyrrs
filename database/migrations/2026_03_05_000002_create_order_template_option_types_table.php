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
        Schema::create('order_template_option_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_template_option_id')->constrained('order_template_options')->onDelete('cascade');
            $table->string('type_name', 255);
            $table->boolean('is_available')->default(true);
            $table->integer('position')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_template_option_types');
    }
};
