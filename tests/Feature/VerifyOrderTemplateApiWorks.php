<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\OrderTemplate;
use Tests\TestCase;

/**
 * Quick verification that the API endpoint works after the fix
 */
class VerifyOrderTemplateApiWorks extends TestCase
{
    /**
     * Test that product 1 has orderTemplate relationship and API returns 200
     */
    public function test_api_returns_order_template_after_fix(): void
    {
        echo "\n\n=== VERIFICATION: After Adding orderTemplate() Relationship ===\n";

        // First, check using direct database query
        echo "\n1. Checking if OrderTemplate exists for product_id = 1...\n";
        $orderTemplate = OrderTemplate::where('product_id', 1)->first();
        
        if ($orderTemplate) {
            echo "   ✓ OrderTemplate found with ID: {$orderTemplate->id}\n";
        } else {
            echo "   ⚠ WARNING: No OrderTemplate found for product_id = 1\n";
            echo "   This means the data hasn't been seeded yet.\n";
            echo "   Creating a sample OrderTemplate for testing...\n";
            
            // Create a minimal order template for product 1
            $product = Product::findOrFail(1);
            $orderTemplate = $product->orderTemplate()->create();
            echo "   ✓ Created OrderTemplate with ID: {$orderTemplate->id}\n";
        }

        // Now test the API endpoint
        echo "\n2. Testing API endpoint: GET /api/customer-orders/product/1/template\n";
        $response = $this->getJson('/api/customer-orders/product/1/template');
        
        echo "   Status Code: {$response->status()}\n";
        
        if ($response->status() === 200) {
            echo "   ✓ SUCCESS: API returns 200\n";
            $data = $response->json();
            if ($data['success']) {
                echo "   ✓ Response has success = true\n";
                if (isset($data['data']['template'])) {
                    echo "   ✓ Template data present\n";
                    $template = $data['data']['template'];
                    echo "     - Options: " . count($template['options']) . "\n";
                    echo "     - Pricings: " . count($template['pricings']) . "\n";
                    echo "     - Discounts: " . count($template['discounts']) . "\n";
                    echo "     - Min Order: " . $template['min_order'] . "\n";
                    echo "     - Layout Fee: " . $template['layout_fee'] . "\n";
                }
            }
        } else {
            echo "   ✗ FAILED: API returns {$response->status()}\n";
            $error = $response->json('error');
            if ($error) {
                echo "   Error: {$error}\n";
            }
        }

        $this->assertTrue($response->status() === 200, 'API should return 200 after fix');
    }
}
