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
        Schema::create('orders', function (Blueprint $table) {
            // The standard envelope requirements
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); 
            $table->string('status')->default('pending'); 
            
            // The custom Excel requirements
            $table->string('general_gdrive_link')->nullable();
            $table->date('expected_delivery_date')->nullable();
            $table->decimal('rush_fee', 8, 2)->default(0);
            $table->decimal('manual_discount', 8, 2)->default(0);
            $table->decimal('grand_total', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
