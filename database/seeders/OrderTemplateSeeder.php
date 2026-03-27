<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\OrderTemplate;
use App\Models\OrderTemplateOption;
use App\Models\OrderTemplateOptionType;
use App\Models\OrderTemplatePricing;
use App\Models\OrderTemplateDiscount;
use App\Models\OrderTemplateMinOrder;
use App\Models\OrderTemplateLayoutFee;
use Illuminate\Database\Seeder;

class OrderTemplateSeeder extends Seeder
{
    /**
     * Seed the order templates for existing products
     */
    public function run(): void
    {
        // Only seed if the order template doesn't already exist
        $product = Product::find(1);
        
        if (!$product) {
            echo "Product 1 not found. Skipping order template seeding.\n";
            return;
        }

        // Check if order template already exists
        if ($product->orderTemplate()->exists()) {
            echo "Order template already exists for product 1. Skipping.\n";
            return;
        }

        echo "Creating order template for product: {$product->name}\n";

        // Create the order template
        $orderTemplate = $product->orderTemplate()->create();
        echo "✓ Created OrderTemplate with ID: {$orderTemplate->id}\n";

        // Create sample options
        echo "Creating sample options...\n";
        
        // Option 1: Finish Type
        $finishOption = $orderTemplate->options()->create([
            'label' => 'Finish Type',
            'position' => 1,
        ]);
        
        $finishOption->optionTypes()->createMany([
            ['type_name' => 'Glossy', 'position' => 1, 'is_available' => true],
            ['type_name' => 'Matte', 'position' => 2, 'is_available' => true],
            ['type_name' => 'Holographic', 'position' => 3, 'is_available' => true],
        ]);
        echo "✓ Created Finish Type option with 3 types\n";

        // Option 2: Size
        $sizeOption = $orderTemplate->options()->create([
            'label' => 'Size',
            'position' => 2,
        ]);
        
        $sizeOption->optionTypes()->createMany([
            ['type_name' => '2x2 inches', 'position' => 1, 'is_available' => true],
            ['type_name' => '3x3 inches', 'position' => 2, 'is_available' => true],
            ['type_name' => '4x4 inches', 'position' => 3, 'is_available' => true],
        ]);
        echo "✓ Created Size option with 3 types\n";

        // Create sample pricings
        echo "Creating sample pricing configurations...\n";
        
        $pricings = [
            ['combination_key' => 'glossy_2x2', 'price' => 0.50],
            ['combination_key' => 'glossy_3x3', 'price' => 0.75],
            ['combination_key' => 'glossy_4x4', 'price' => 1.25],
            ['combination_key' => 'matte_2x2', 'price' => 0.60],
            ['combination_key' => 'matte_3x3', 'price' => 0.85],
            ['combination_key' => 'matte_4x4', 'price' => 1.35],
            ['combination_key' => 'holographic_2x2', 'price' => 1.00],
            ['combination_key' => 'holographic_3x3', 'price' => 1.50],
            ['combination_key' => 'holographic_4x4', 'price' => 2.00],
        ];
        
        foreach ($pricings as $pricing) {
            $orderTemplate->pricings()->create($pricing);
        }
        echo "✓ Created " . count($pricings) . " pricing configurations\n";

        // Create sample discounts based on quantity
        echo "Creating volume discount tiers...\n";
        
        $orderTemplate->discounts()->createMany([
            ['min_quantity' => 100, 'price_reduction' => 0.05, 'position' => 1],
            ['min_quantity' => 500, 'price_reduction' => 0.10, 'position' => 2],
            ['min_quantity' => 1000, 'price_reduction' => 0.15, 'position' => 3],
        ]);
        echo "✓ Created 3 volume discount tiers\n";

        // Create minimum order requirement
        echo "Creating minimum order requirement...\n";
        $orderTemplate->minOrder()->create([
            'min_quantity' => 50,
        ]);
        echo "✓ Set minimum order quantity to 50\n";

        // Create layout fee
        echo "Creating layout fee...\n";
        $orderTemplate->layoutFee()->create([
            'fee_amount' => 25.00,
        ]);
        echo "✓ Set layout fee to \$25.00\n";

        echo "\n✓ All data created successfully!\n";
    }
}
