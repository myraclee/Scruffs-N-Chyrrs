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
        Schema::create('rush_fees', function (Blueprint $table) {
            $table->id();
            $table->string('label');     // e.g., "Below ₱3,000"
            $table->decimal('min_price', 10, 2);
            $table->decimal('max_price', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rush_fees');
    }
};
