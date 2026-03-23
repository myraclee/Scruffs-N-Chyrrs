<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Product;
use Illuminate\Support\Str;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Add slug column if it doesn't exist
            if (!Schema::hasColumn('products', 'slug')) {
                $table->string('slug')->unique()->after('name')->nullable();
            }
        });

        // Populate existing products with slugs if they don't have one
        Product::whereNull('slug')->each(function ($product) {
            $product->slug = Str::slug($product->name);
            $product->save();
        });

        // Make slug NOT NULL (optional - only if all products have slugs now)
        if (Product::whereNull('slug')->count() === 0) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('slug')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
