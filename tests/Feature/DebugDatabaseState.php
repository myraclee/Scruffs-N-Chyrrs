<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductPriceImage;
use Tests\TestCase;

/**
 * DEBUG TEST: Direct database verification without factories
 * 
 * Works with existing database data to diagnose issues
 */
class DebugDatabaseState extends TestCase
{
    public function test_check_actual_products_in_database(): void
    {
        echo "\n========== ACTUAL DATABASE STATE ==========\n\n";

        try {
            // Check if products table exists and has data
            $productCount = Product::query()->count();
            echo "Total Products in database: " . $productCount . "\n";

            if ($productCount === 0) {
                echo "\n⚠ WARNING: No products found in database!\n";
                echo "This is why price images can't display.\n\n";
                return;
            }

            // Get first product for detailed inspection
            $product = Product::query()->first();
            
            echo "\nFirst Product Details:\n";
            echo "  - ID: " . $product->id . "\n";
            echo "  - Name: " . $product->name . "\n";
            echo "  - Slug: " . $product->slug . "\n";
            echo "  - Description: " . substr($product->description ?? '', 0, 50) . "...\n";
            echo "  - Cover Image: " . $product->cover_image_path . "\n";

            // Check price images for this product
            $priceImageCount = $product->priceImages()->count();
            echo "\n  Price Images Count: " . $priceImageCount . "\n";

            if ($priceImageCount === 0) {
                echo "  ⚠ WARNING: This product has NO price images!\n";
                echo "  This is why the price gallery appears empty.\n";
            } else {
                echo "\n  Price Images Details:\n";
                $product->priceImages()->each(function($image, $i) {
                    echo "    Image " . ($i + 1) . ":\n";
                    echo "      - ID: " . $image->id . "\n";
                    echo "      - Path: " . $image->image_path . "\n";
                    echo "      - Sort Order: " . $image->sort_order . "\n";
                });
            }

            // Test JSON encoding
            echo "\n--- JSON ENCODING TEST ---\n";
            $product->load('priceImages');
            $encoded = json_encode($product);
            $decoded = json_decode($encoded, true);

            if (isset($decoded['price_images'])) {
                echo "✓ price_images key present in JSON\n";
                echo "  Contains " . count($decoded['price_images']) . " items\n";
            } else {
                echo "✗ price_images key MISSING from JSON\n";
                echo "  Available keys: " . implode(', ', array_keys($decoded)) . "\n";
            }

        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
            echo "Stack: " . $e->getTraceAsString() . "\n";
        }

        echo "\n==========================================\n\n";

        $this->assertTrue(true, "Database state checked");
    }

    public function test_check_price_image_files_in_storage(): void
    {
        echo "\n========== STORAGE FILES CHECK ==========\n\n";

        try {
            $images = ProductPriceImage::query()->limit(5)->get();
            echo "Found " . $images->count() . " price images in database\n\n";

            if ($images->isEmpty()) {
                echo "No price images in database to check\n";
                return;
            }

            foreach ($images as $index => $image) {
                echo "Image " . ($index + 1) . ":\n";
                echo "  Product ID: " . $image->product_id . "\n";
                echo "  Path in DB: " . $image->image_path . "\n";

                // Construct URL as JavaScript would
                $jsPath = $image->image_path;
                $jsUrl = $jsPath[0] === '/' ? $jsPath : '/storage/' . $jsPath;
                echo "  JS would use: " . $jsUrl . "\n";

                // Check different file locations
                $pathsToCheck = [
                    'public/' . ltrim($jsUrl, '/'),
                    public_path(ltrim($jsUrl, '/')),
                    storage_path('app/public/' . ltrim($image->image_path, '/')),
                ];

                $found = false;
                foreach ($pathsToCheck as $checkPath) {
                    if (file_exists($checkPath)) {
                        echo "  ✓ File exists at: " . $checkPath . "\n";
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    echo "  ✗ File NOT found in any location\n";
                }
                echo "\n";
            }

        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }

        echo "==========================================\n\n";

        $this->assertTrue(true, "Storage files checked");
    }

    public function test_verify_filesystem_config(): void
    {
        echo "\n========== FILESYSTEM CONFIGURATION ==========\n\n";

        try {
            $default = config('filesystems.default');
            echo "Default Filesystem: " . $default . "\n";

            $publicDisk = config('filesystems.disks.public');
            echo "\nPublic Disk Config:\n";
            foreach ($publicDisk as $key => $value) {
                if (!is_array($value)) {
                    echo "  " . $key . ": " . $value . "\n";
                }
            }

            echo "\nDirectory Checks:\n";
            echo "  Public root: " . (is_dir(public_path()) ? '✓ exists' : '✗ missing') . "\n";
            echo "  Public/storage: " . (is_dir(public_path('storage')) ? '✓ exists' : '✗ missing') . "\n";
            echo "  Storage/app/public: " . (is_dir(storage_path('app/public')) ? '✓ exists' : '✗ missing') . "\n";

        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }

        echo "\n============================================\n\n";

        $this->assertTrue(true, "Filesystem config checked");
    }
}
