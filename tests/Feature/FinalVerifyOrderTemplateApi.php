<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Final verification that the Order Template API works correctly
 */
class FinalVerifyOrderTemplateApi extends TestCase
{
    /**
     * Test the actual API endpoint returns 200 and valid data
     */
    public function test_order_template_api_endpoint_returns_valid_response(): void
    {
        echo "\n=== FINAL VERIFICATION: Order Template API Endpoint ===\n\n";
        
        echo "Testing: GET /api/customer-orders/product/1/template\n";
        echo "Expected: 200 OK with valid template data\n\n";
        
        $response = $this->getJson('/api/customer-orders/product/1/template');
        
        echo "Response Status: {$response->status()}\n";
        
        // Verify status code
        if ($response->status() !== 200) {
            echo "❌ FAILED: Expected 200, got {$response->status()}\n";
            echo "Response: " . json_encode($response->json(), JSON_PRETTY_PRINT) . "\n";
            $this->fail("API should return 200 status code");
        }
        
        echo "✅ Status: 200 OK\n\n";
        
        // Verify response structure
        $data = $response->json();
        
        echo "Response Structure:\n";
        
        // Check success flag
        if ($data['success'] !== true) {
            echo "❌ Expected success=true\n";
            $this->fail("Response should have success=true");
        }
        echo "✅ success: true\n";
        
        // Check data exists
        if (!isset($data['data'])) {
            echo "❌ Response missing 'data' key\n";
            $this->fail("Response should have 'data' key");
        }
        echo "✅ data: present\n";
        
        // Check product info
        $productData = $data['data']['product'] ?? null;
        if (!$productData) {
            echo "❌ Response missing product data\n";
            $this->fail("Response should have product data");
        }
        echo "✅ product: " . $productData['name'] . " (ID: " . $productData['id'] . ")\n";
        
        // Check template data
        $templateData = $data['data']['template'] ?? null;
        if (!$templateData) {
            echo "❌ Response missing template data\n";
            $this->fail("Response should have template data");
        }
        echo "✅ template: present\n";
        
        // Verify template contains required keys
        $templateKeys = ['options', 'pricings', 'discounts', 'min_order', 'layout_fee'];
        foreach ($templateKeys as $key) {
            if (!isset($templateData[$key])) {
                echo "❌ Template missing required key: {$key}\n";
                $this->fail("Template should have {$key}");
            }
        }
        
        echo "\nTemplate Contents:\n";
        echo "  • Options: " . count($templateData['options']) . "\n";
        echo "  • Pricings: " . count($templateData['pricings']) . "\n";
        echo "  • Discounts: " . count($templateData['discounts']) . "\n";
        echo "  • Min Order: " . $templateData['min_order'] . " units\n";
        echo "  • Layout Fee: \$" . number_format($templateData['layout_fee'], 2) . "\n";
        
        // Check rush fees
        $rushFees = $data['data']['rush_fees'] ?? null;
        if ($rushFees !== null) {
            echo "  • Rush Fees: " . count($rushFees) . "\n";
        }
        
        echo "\n✅ ALL CHECKS PASSED!\n";
        echo "✅ The Order Now button should now work correctly.\n\n";
        
        $this->assertTrue(true);
    }
}
