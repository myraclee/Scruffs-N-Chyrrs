<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('faq_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->integer('sort_order')->default(1);
            $table->timestamps();

            $table->index('sort_order');
        });

        // Seed 4 default categories
        $categories = [
            ['name' => 'General Questions', 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Shipping & Orders', 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Customization & Finishes', 'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Pricing & Discounts', 'sort_order' => 4, 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('faq_categories')->insert($categories);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('faq_categories');
    }
};
