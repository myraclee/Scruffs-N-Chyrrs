<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Verify end-to-end pricing fix
 */
class VerifyPricingFixEndToEnd extends TestCase
{
    /**
     * Test that API returns correct pricing data with numeric keys
     */
    public function test_api_returns_numeric_pricing_keys(): void
    {
        echo "\n\n=== VERIFICATION: Pricing Fix End-to-End ===\n\n";

        $response = $this->getJson('/api/customer-orders/product/1/template');
        
        echo "1. API Response Status: {$response->status()}\n";
        $this->assertEquals(200, $response->status(), 'API should return 200');
        echo "   ✓ API returned 200 OK\n\n";

        $data = $response->json();
        $template = $data['data']['template'] ?? null;
        
        if (!$template) {
            echo "❌ No template data\n";
            $this->fail("Template data missing");
        }

        echo "2. Pricing Data Structure:\n";
        $pricings = $template['pricings'] ?? [];
        echo "   Found " . count($pricings) . " pricing entries\n";
        
        foreach ($pricings as $pricing) {
            echo "   - Key: '{$pricing['combination_key']}' → Price: \${$pricing['price']}\n";
        }

        // Verify keys are numeric
        echo "\n3. Verifying Combination Keys Format:\n";
        $hasNumericKeys = true;
        foreach ($pricings as $pricing) {
            $key = $pricing['combination_key'];
            // Check if key is numeric or comma-separated numbers
            if (!preg_match('/^\d+(,\d+)*$/', $key)) {
                echo "   ❌ Key '$key' is not numeric format\n";
                $hasNumericKeys = false;
            } else {
                echo "   ✓ Key '$key' is correct numeric format\n";
            }
        }
        
        if (!$hasNumericKeys) {
            $this->fail("Pricing keys should be numeric IDs");
        }

        echo "\n4. Options Data:\n";
        $options = $template['options'] ?? [];
        echo "   Found " . count($options) . " options\n";
        
        foreach ($options as $option) {
            echo "   - {$option['label']} (ID: {$option['id']})\n";
            foreach ($option['option_types'] as $type) {
                echo "     └─ {$type['type_name']} (ID: {$type['id']})\n";
            }
        }

        echo "\n5. Simulating JavaScript Selection:\n";
        // Simulate selecting first option type from first option
        $firstOption = $options[0];
        $firstType = $firstOption['option_types'][0];
        echo "   Selected: {$firstType['type_name']} (ID: {$firstType['id']})\n";
        
        // Build combination key the way JavaScript does
        $selectedOptions = [$firstOption['id'] => $firstType['id']];
        $ids = array_values($selectedOptions);
        sort($ids, SORT_NUMERIC);
        $generatedKey = implode(',', $ids);
        echo "   Generated combination_key: '$generatedKey'\n";

        // Check if this key exists in pricings
        echo "\n6. Checking Pricing Match:\n";
        $found = false;
        foreach ($pricings as $pricing) {
            if ($pricing['combination_key'] === $generatedKey) {
                echo "   ✓ MATCH FOUND!\n";
                echo "   Price for selection: \${$pricing['price']}\n";
                $found = true;
                break;
            }
        }

        if (!$found) {
            echo "   ❌ NO MATCH!\n";
            $this->fail("Generated combination key '$generatedKey' not found in pricings");
        }

        echo "\n✅ ALL TESTS PASSED!\n";
        echo "✅ Pricing fix is working correctly.\n";
        echo "\nThe Order Now modal should now display prices correctly!\n";
    }
}
