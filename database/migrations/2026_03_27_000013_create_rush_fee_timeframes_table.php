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
        Schema::create('rush_fee_timeframes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rush_fee_id')->constrained('rush_fees')->onDelete('cascade');
            $table->string('label');     // e.g., "2 days", "5 days"
            $table->decimal('percentage', 5, 2);  // Rush fee percentage
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rush_fee_timeframes');
    }
};
