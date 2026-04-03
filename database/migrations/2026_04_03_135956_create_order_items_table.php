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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            // This firmly attaches the item to the main order envelope
            $table->foreignId('order_id')->constrained()->cascadeOnDelete(); 
            
            // The specific details from the SA's modal
            $table->string('category'); // e.g., 'Stickers', 'Button Pins'
            $table->string('design_name_link')->nullable(); // The "DRIVE FILE NAME"
            $table->string('type')->nullable();
            $table->string('lamination')->nullable();
            $table->integer('quantity')->default(1);
            
            // The math
            $table->decimal('layout_fee', 8, 2)->default(0);
            $table->decimal('item_total', 10, 2)->default(0);
            $table->timestamps();
        });
    }
};
