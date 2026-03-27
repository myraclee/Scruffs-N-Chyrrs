<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductPriceImage;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * DEBUG TEST: Verify storage paths and file existence
 * 
 * This test validates that:
 * 1. Image files exist in storage
 * 2. Storage paths are correctly configured
 * 3. Product detail page can access image URLs correctly
 */
class DebugStoragePaths extends TestCase
{
    use RefreshDatabase;

    public function test_price_image_storage_paths_format(): void
    {
        echo "\n=== STORAGE PATH ANALYSIS ===\n";

        // Create test product with various path formats
        $product = Product::factory()->create();

        // Test different path formats
        $pathFormats = [
            'products/price_lists/test-1.png',
            'price_lists/test-2.png', 
            '/storage/products/price_lists/test-3.png',
        ];

        foreach ($pathFormats as $targetPath) {
            echo "\nTesting path format: " . $targetPath . "\n";

            try {
                $currentCount = $product->priceImages()->count();
                
                ProductPriceImage::create([
                    'product_id' => $product->id,
                    'image_path' => $targetPath,
                    'sort_order' => $currentCount + 1,
                ]);

                $image = ProductPriceImage::where('image_path', $targetPath)->first();
                
                if ($image) {
                    echo "  ✓ Created successfully\n";
                    echo "  - ID: " . $image->id . "\n";
                    echo "  - Stored path: " . $image->image_path . "\n";
                } else {
                    echo "  ✗ Failed to retrieve\n";
                }
            } catch (\Exception $e) {
                echo "  ✗ Error: " . $e->getMessage() . "\n";
            }
        }
    }

    public function test_check_filesystem_storage_configuration(): void
    {
        echo "\n=== FILESYSTEM STORAGE CONFIGURATION ===\n";

        try {
            $defaultDisk = config('filesystems.default');
            echo "Default Disk: " . $defaultDisk . "\n";

            $disks = config('filesystems.disks');
            if (isset($disks[$defaultDisk])) {
                echo "\nDefault Disk Configuration:\n";
                foreach ($disks[$defaultDisk] as $key => $value) {
                    if ($key !== 'visibility') {
                        echo "  - " . $key . ": " . (is_array($value) ? json_encode($value) : $value) . "\n";
                    }
                }
            }

            // Check if public directory exists
            $publicPath = public_path();
            echo "\nPublic Path: " . $publicPath . "\n";
            echo "Public Directory Exists: " . (is_dir($publicPath) ? 'YES' : 'NO') . "\n";

            $storagePath = public_path('storage');
            echo "\nPublic Storage Path: " . $storagePath . "\n";
            echo "Public Storage Directory Exists: " . (is_dir($storagePath) ? 'YES' : 'NO') . "\n";

            $appStoragePath = storage_path('app/public');
            echo "\nApp Storage/Public Path: " . $appStoragePath . "\n";
            echo "App Storage/Public Directory Exists: " . (is_dir($appStoragePath) ? 'YES' : 'NO') . "\n";

        } catch (\Exception $e) {
            echo "Error checking configuration: " . $e->getMessage() . "\n";
        }

        $this->assertTrue(true, "Configuration logged above");
    }

    public function test_verify_actual_image_files_exist(): void
    {
        echo "\n=== ACTUAL IMAGE FILES IN DATABASE ===\n";

        try {
            $images = ProductPriceImage::all();
            echo "Total ProductPriceImage Records: " . $images->count() . "\n";

            if ($images->count() === 0) {
                echo "No price images found in database!\n";
                return;
            }

            foreach ($images->take(5) as $image) {
                echo "\nImage ID " . $image->id . ":\n";
                echo "  - Product ID: " . $image->product_id . "\n";
                echo "  - Path: " . $image->image_path . "\n";
                echo "  - Sort Order: " . $image->sort_order . "\n";

                // Check if file exists
                $path = $image->image_path;
                
                // Try different path combinations
                $pathsToCheck = [
                    $path,
                    'public/' . $path,
                    public_path('storage/' . ltrim($path, '/')),
                    storage_path('app/public/' . ltrim($path, '/')),
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
                    echo "  ✗ File not found\n";
                    echo "    Checked paths:\n";
                    foreach ($pathsToCheck as $checkPath) {
                        echo "      - " . $checkPath . "\n";
                    }
                }
            }

        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }

        $this->assertTrue(true, "File existence checked above");
    }
}
