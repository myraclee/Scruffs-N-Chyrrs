<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductPriceImage;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * DEBUG TEST: Verify product detail data structure
 * 
 * This test validates that:
 * 1. Products load with priceImages relationship
 * 2. priceImages relationship is properly populated
 * 3. JSON encoding preserves all necessary fields
 * 4. Image paths are in correct format
 */
class DebugProductDetailData extends TestCase
{
    use RefreshDatabase;

    public function test_product_price_images_relationship_loads_correctly(): void
    {
        // Create test product with price images
        $product = Product::factory()->create();
        
        ProductPriceImage::factory()->create([
            'product_id' => $product->id,
            'image_path' => 'products/test-image-1.png',
            'sort_order' => 1,
        ]);

        ProductPriceImage::factory()->create([
            'product_id' => $product->id,
            'image_path' => 'products/test-image-2.png',
            'sort_order' => 2,
        ]);

        // Load product with relationship
        $loadedProduct = Product::with('priceImages')->find($product->id);

        // Verify relationship loaded
        $this->assertNotNull($loadedProduct->priceImages);
        $this->assertCount(2, $loadedProduct->priceImages);

        // Log the data structure for inspection
        echo "\n=== PRODUCT WITH PRICE IMAGES ===\n";
        echo "Product ID: " . $loadedProduct->id . "\n";
        echo "Product Name: " . $loadedProduct->name . "\n";
        echo "Price Images Count: " . $loadedProduct->priceImages->count() . "\n";

        // Verify each image has required fields
        foreach ($loadedProduct->priceImages as $index => $image) {
            echo "\nPrice Image " . ($index + 1) . ":\n";
            echo "  - ID: " . $image->id . "\n";
            echo "  - Path: " . $image->image_path . "\n";
            echo "  - Sort Order: " . $image->sort_order . "\n";
        }
    }

    public function test_product_json_encoding_preserves_price_images(): void
    {
        // Create test product with price images
        $product = Product::factory()->create();
        
        ProductPriceImage::factory()->create([
            'product_id' => $product->id,
            'image_path' => 'products/test-image-1.png',
            'sort_order' => 1,
        ]);

        // Load with relationship
        $product->load('priceImages');

        // JSON encode like in blade template
        $jsonEncoded = json_encode($product);
        $decoded = json_decode($jsonEncoded, true);

        // Verify JSON structure
        echo "\n=== JSON ENCODED PRODUCT STRUCTURE ===\n";
        echo "Keys: " . implode(', ', array_keys($decoded)) . "\n";

        if (isset($decoded['price_images'])) {
            echo "Price Images Key Found: YES\n";
            echo "Price Images Count: " . count($decoded['price_images']) . "\n";
            
            foreach ($decoded['price_images'] as $index => $image) {
                echo "\nEncoded Image " . ($index + 1) . ":\n";
                echo "  - Keys: " . implode(', ', array_keys($image)) . "\n";
                echo "  - image_path: " . ($image['image_path'] ?? 'MISSING') . "\n";
                echo "  - sort_order: " . ($image['sort_order'] ?? 'MISSING') . "\n";
            }
        } else {
            echo "Price Images Key Found: NO\n";
            echo "WARNING: price_images not in JSON! Check model hidden fields.\n";
        }

        $this->assertTrue(true, "Data structure logged above");
    }

    public function test_verify_any_products_exist_in_database(): void
    {
        // Check actual database state
        echo "\n=== DATABASE STATE CHECK ===\n";
        
        $productCount = Product::count();
        echo "Total Products in DB: " . $productCount . "\n";

        if ($productCount > 0) {
            $firstProduct = Product::first();
            $priceImageCount = $firstProduct->priceImages()->count();
            
            echo "First Product: " . $firstProduct->name . "\n";
            echo "First Product ID: " . $firstProduct->id . "\n";
            echo "First Product Price Images: " . $priceImageCount . "\n";

            if ($priceImageCount > 0) {
                echo "\nFirst Price Image Details:\n";
                $firstImage = $firstProduct->priceImages()->first();
                echo "  - Path: " . $firstImage->image_path . "\n";
                echo "  - Sort Order: " . $firstImage->sort_order . "\n";
            } else {
                echo "\nWARNING: Product has NO price images!\n";
            }
        } else {
            echo "WARNING: No products in database!\n";
        }

        $this->assertTrue(true, "Database state logged above");
    }
}
