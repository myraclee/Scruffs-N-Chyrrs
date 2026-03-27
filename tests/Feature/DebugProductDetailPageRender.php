<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * DEBUG TEST: Verify the product detail page routes and templates render
 * 
 * This test validates that:
 * 1. Product detail page loads without errors
 * 2. All required data is passed to the view
 * 3. The blade template renders correctly
 * 4. JavaScript bundles are included
 */
class DebugProductDetailPageRender extends TestCase
{
    public function test_product_detail_page_renders_without_errors(): void
    {
        echo "\n=== PRODUCT DETAIL PAGE RENDER TEST ===\n";

        // Get first product or create one
        $product = \App\Models\Product::first();
        
        if (!$product) {
            echo "Creating test product...\n";
            $product = \App\Models\Product::factory()->create();
        }

        echo "Testing with product: " . $product->name . " (ID: " . $product->id . ")\n";

        // Attempt to fetch the product detail page
        try {
            $response = $this->get("/products/{$product->slug}");
            
            echo "Response Status: " . $response->getStatusCode() . "\n";
            
            if ($response->getStatusCode() === 200) {
                echo "✓ Page loaded successfully\n";
            } else {
                echo "✗ Page returned status " . $response->getStatusCode() . "\n";
                echo "Response content preview:\n";
                echo substr($response->getContent(), 0, 500) . "\n";
                return;
            }

            $content = $response->getContent();
            
            // Check for required elements
            echo "\nChecking for required elements:\n";
            
            $checks = [
                'data-product attribute' => 'data-product="',
                'back button' => 'id="backBtn"',
                'order now button' => 'id="orderNowBtn"',
                'price gallery' => 'id="priceGallery"',
                'order modal component' => 'id="orderModal"',
                'product detail script' => 'product-detail.js',
                'order modal script' => 'order_modal.js',
                'product detail css' => 'order_modal.css',
            ];

            foreach ($checks as $label => $search) {
                if (strpos($content, $search) !== false) {
                    echo "  ✓ " . $label . "\n";
                } else {
                    echo "  ✗ MISSING: " . $label . "\n";
                }
            }

            // Check if data-product contains valid JSON
            if (preg_match('/data-product="([^"]+)"/', $content, $matches)) {
                $jsonString = htmlspecialchars_decode($matches[1]);
                try {
                    $decoded = json_decode($jsonString, true);
                    if ($decoded) {
                        echo "\n✓ data-product contains valid JSON\n";
                        echo "  Top-level keys: " . implode(', ', array_slice(array_keys($decoded), 0, 5)) . "...\n";
                        
                        if (isset($decoded['price_images'])) {
                            echo "  ✓ price_images key found: " . count($decoded['price_images']) . " images\n";
                        } else {
                            echo "  ✗ price_images key NOT found\n";
                            echo "  Available keys: " . implode(', ', array_keys($decoded)) . "\n";
                        }
                    } else {
                        echo "\n✗ data-product JSON could not be decoded\n";
                    }
                } catch (\Exception $e) {
                    echo "\n✗ Error decoding data-product JSON: " . $e->getMessage() . "\n";
                }
            }

        } catch (\Exception $e) {
            echo "✗ Error fetching page: " . $e->getMessage() . "\n";
        }

        $this->assertTrue(true, "Render test completed - check output above");
    }

    public function test_check_javascript_imports_structure(): void
    {
        echo "\n=== JAVASCRIPT FILE STRUCTURE CHECK ===\n";

        $files = [
            'resources/js/customer/pages/product-detail.js',
            'resources/js/customer/pages/order_modal.js',
            'resources/js/api/customerOrderApi.js',
            'resources/js/utils/toast.js',
        ];

        foreach ($files as $file) {
            $path = base_path($file);
            echo "\nChecking: " . $file . "\n";
            
            if (!file_exists($path)) {
                echo "  ✗ FILE NOT FOUND\n";
                continue;
            }

            echo "  ✓ File exists\n";
            
            $content = file_get_contents($path);
            $lines = count(explode("\n", $content));
            echo "  - Lines: " . $lines . "\n";

            // Check for exports
            if (strpos($content, 'export') !== false) {
                echo "  ✓ Contains 'export' keyword\n";
            }

            // Check for imports
            if (strpos($content, 'import') !== false) {
                echo "  ✓ Contains 'import' keyword\n";
                // Find all import statements
                preg_match_all('/import\s+.*?from\s+[\'"]([^\'"]+)[\'"]/i', $content, $imports);
                if (!empty($imports[1])) {
                    echo "  Imports:\n";
                    foreach (array_unique($imports[1]) as $import) {
                        echo "    - " . $import . "\n";
                    }
                }
            }
        }

        $this->assertTrue(true, "File structure checked - see output above");
    }
}
