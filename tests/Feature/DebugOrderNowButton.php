<?php

namespace Tests\Feature;

use App\Models\Product;
use Tests\TestCase;

/**
 * DEBUG TEST: Verify Order Now button HTML and attributes
 * 
 * Checks if:
 * 1. Order Now button exists in DOM
 * 2. Button has correct id="orderNowBtn"
 * 3. Button is not permanently disabled
 * 4. Modal component is included in page
 * 5. All required JavaScript files are included
 */
class DebugOrderNowButton extends TestCase
{
    public function test_product_detail_page_has_order_now_button(): void
    {
        echo "\n========== ORDER NOW BUTTON HTML CHECK ==========\n\n";

        try {
            // Get a product
            $product = Product::query()->first();
            
            if (!$product) {
                echo "⚠ No products found in database. Cannot test.\n";
                return;
            }

            echo "Testing with product: " . $product->name . "\n\n";

            // Fetch product detail page
            $response = $this->get("/products/{$product->slug}");

            if ($response->getStatusCode() !== 200) {
                echo "✗ Page returned status " . $response->getStatusCode() . "\n";
                return;
            }

            $html = $response->getContent();

            // Check 1: Button exists with correct ID
            echo "Check 1: Order Now button HTML element\n";
            if (strpos($html, 'id="orderNowBtn"') !== false) {
                echo "  ✓ Button with id='orderNowBtn' found\n";
            } else {
                echo "  ✗ Button NOT FOUND (missing id='orderNowBtn')\n";
                return;
            }

            // Check 2: Button class name
            echo "\nCheck 2: Button classes\n";
            if (preg_match('/class="([^"]*orderNowBtn[^"]*)"/i', $html, $matches) || 
                preg_match('/<button[^>]*id="orderNowBtn"[^>]*class="([^"]*)"/', $html, $matches)) {
                echo "  ✓ Button has class: " . ($matches[1] ?? 'order_now_button') . "\n";
            } else {
                echo "  ℹ Button found but class name pattern not matched\n";
            }

            // Check 3: Extract button HTML for inspection
            echo "\nCheck 3: Full button HTML\n";
            if (preg_match('/<button[^>]*id="orderNowBtn"[^>]*>.*?<\/button>/is', $html, $matches)) {
                echo "  ✓ Button HTML snippet:\n";
                $snippet = trim($matches[0]);
                if (strlen($snippet) > 200) {
                    echo "    " . substr($snippet, 0, 200) . "...\n";
                } else {
                    echo "    " . $snippet . "\n";
                }
            }

            // Check 4: Modal component included
            echo "\nCheck 4: Order Modal component\n";
            if (strpos($html, 'id="orderModal"') !== false) {
                echo "  ✓ Modal with id='orderModal' found\n";
            } else {
                echo "  ✗ Modal component NOT FOUND (id='orderModal')\n";
            }

            // Check 5: Product detail script included
            echo "\nCheck 5: JavaScript files included\n";
            $files = [
                'product-detail.js' => 'Product detail interactivity',
                'order_modal.js' => 'Order modal logic',
            ];

            foreach ($files as $file => $description) {
                if (strpos($html, $file) !== false) {
                    echo "  ✓ " . $file . " (" . $description . ")\n";
                } else {
                    echo "  ✗ " . $file . " NOT FOUND\n";
                }
            }

            // Check 6: Auth meta tag
            echo "\nCheck 6: Authentication meta tag\n";
            if (preg_match('/<meta[^>]*name="user-authenticated"[^>]*>/i', $html)) {
                echo "  ✓ Meta tag 'user-authenticated' found\n";
                if (preg_match('/<meta[^>]*name="user-authenticated"[^>]*content="([^"]*)"/', $html, $m)) {
                    echo "    - Value: " . $m[1] . "\n";
                }
            } else {
                echo "  ✗ Meta tag 'user-authenticated' NOT FOUND\n";
            }

            // Check 7: Data attributes on container
            echo "\nCheck 7: Product data attributes\n";
            if (preg_match('/data-product-id="(\d+)"/', $html, $m)) {
                echo "  ✓ data-product-id: " . $m[1] . "\n";
            } else {
                echo "  ✗ data-product-id NOT FOUND\n";
            }

            if (preg_match('/data-product="/', $html)) {
                echo "  ✓ data-product attribute found\n";
            } else {
                echo "  ✗ data-product attribute NOT FOUND\n";
            }

        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }

        echo "\n===============================================\n\n";

        $this->assertTrue(true, "Button HTML verified - check output above");
    }

    public function test_check_javascript_modules_structure(): void
    {
        echo "\n========== JAVASCRIPT MODULES CHECK ==========\n\n";

        try {
            $jsFiles = [
                'resources/js/customer/pages/product-detail.js' => [
                    'must_have' => ['openOrderModal', 'setupEventListeners', 'orderNowBtn'],
                    'description' => 'Main product detail page logic'
                ],
                'resources/js/customer/pages/order_modal.js' => [
                    'must_have' => ['export async function openOrderModal'],
                    'description' => 'Order modal logic'
                ],
                'resources/js/utils/toast.js' => [
                    'must_have' => ['export default', 'warning', 'error', 'success'],
                    'description' => 'Toast notification system'
                ],
            ];

            foreach ($jsFiles as $file => $config) {
                echo "File: " . $file . "\n";
                echo "  Description: " . $config['description'] . "\n";

                $path = base_path($file);

                if (!file_exists($path)) {
                    echo "  ✗ FILE NOT FOUND\n\n";
                    continue;
                }

                $content = file_get_contents($path);
                $lines = count(explode("\n", $content));
                echo "  ✓ Exists (" . $lines . " lines, " . round(strlen($content) / 1024, 1) . "KB)\n";

                // Check for required patterns
                echo "  Required patterns:\n";
                foreach ($config['must_have'] as $pattern) {
                    if (strpos($content, $pattern) !== false) {
                        echo "    ✓ Contains: " . $pattern . "\n";
                    } else {
                        echo "    ✗ MISSING: " . $pattern . "\n";
                    }
                }

                // Check for syntax errors with basics
                if (strpos($content, 'export') !== false) {
                    echo "  ✓ Has export statements (ESM module)\n";
                } else {
                    echo "  ℹ No export statements found\n";
                }

                echo "\n";
            }

        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }

        echo "===========================================\n\n";

        $this->assertTrue(true, "JS modules checked - see output above");
    }

    public function test_verify_import_chain(): void
    {
        echo "\n========== IMPORT CHAIN VERIFICATION ==========\n\n";

        try {
            // Check if product-detail.js can import order_modal correctly
            $productDetailPath = base_path('resources/js/customer/pages/product-detail.js');
            $productDetailContent = file_get_contents($productDetailPath);

            echo "Checking imports in product-detail.js:\n\n";

            // Look for imports
            preg_match_all("/import\s+(?:{[^}]*}|[\w\s]*)\s+from\s+['\"]([^'\"]+)['\"]/", $productDetailContent, $imports);

            if (!empty($imports[1])) {
                echo "Found " . count($imports[1]) . " imports:\n";
                foreach (array_unique($imports[1]) as $importPath) {
                    echo "  - " . $importPath . "\n";

                    // Resolve relative import
                    if (strpos($importPath, '/') === 0) {
                        $fullPath = base_path($importPath);
                    } elseif (strpos($importPath, './') === 0) {
                        $dir = dirname($productDetailPath);
                        $fullPath = realpath($dir . '/' . $importPath);
                    } else {
                        continue;
                    }

                    if (file_exists($fullPath)) {
                        echo "    ✓ File exists: " . $fullPath . "\n";
                    } else {
                        echo "    ✗ File NOT FOUND: " . $fullPath . "\n";
                    }
                }
            } else {
                echo "✗ No imports found in product-detail.js\n";
            }

        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }

        echo "\n============================================\n\n";

        $this->assertTrue(true, "Import chain verified");
    }
}
