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
        // Add faq_category_id column
        Schema::table('faqs', function (Blueprint $table) {
            $table->foreignId('faq_category_id')
                ->nullable()
                ->after('id')
                ->constrained('faq_categories')
                ->cascadeOnDelete();
        });

        // Migrate existing FAQs to category IDs based on category names
        $categoryMap = [
            'General Questions' => 1,
            'Shipping & Orders' => 2,
            'Customization & Finishes' => 3,
            'Pricing & Discounts' => 4,
        ];

        foreach ($categoryMap as $categoryName => $categoryId) {
            DB::table('faqs')
                ->where('category', $categoryName)
                ->update(['faq_category_id' => $categoryId]);
        }

        // Drop the old category column
        Schema::table('faqs', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('faqs', function (Blueprint $table) {
            $table->string('category')->default('General Questions')->after('id');
        });

        // Migrate data back from IDs to names
        $categoryMap = [
            1 => 'General Questions',
            2 => 'Shipping & Orders',
            3 => 'Customization & Finishes',
            4 => 'Pricing & Discounts',
        ];

        foreach ($categoryMap as $categoryId => $categoryName) {
            DB::table('faqs')
                ->where('faq_category_id', $categoryId)
                ->update(['category' => $categoryName]);
        }

        Schema::table('faqs', function (Blueprint $table) {
            $table->dropForeignKey(['faq_category_id']);
            $table->dropColumn('faq_category_id');
        });
    }
};
