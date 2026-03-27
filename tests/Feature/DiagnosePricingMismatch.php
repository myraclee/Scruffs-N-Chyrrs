<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\OrderTemplate;
use App\Models\OrderTemplateOption;
use App\Models\OrderTemplatePricing;
use Tests\TestCase;

class DiagnosePricingMismatch extends TestCase
{
    /**
     * Diagnosis: Compare what pricing keys exist vs what JavaScript generates
     */
    public function test_pricing_key_mismatch(): void
    {
        echo "\n\n=== DIAGNOSIS: Pricing Key Mismatch ===\n\n";

        $product = Product::find(1);
        if (!$product) {
            echo "❌ Product 1 not found\n";
            $this->fail("Product 1 not found");
        }

        $orderTemplate = $product->orderTemplate;
        if (!$orderTemplate) {
            echo "❌ OrderTemplate not found for product 1\n";
            $this->fail("OrderTemplate not found");
        }

        echo "✓ Found OrderTemplate ID: {$orderTemplate->id}\n\n";

        // Get options and their types
        echo "=== OPTIONS & THEIR TYPES ===\n";
        $optionMap = [];
        
        foreach ($orderTemplate->options as $option) {
            echo "\nOption: {$option->label} (ID: {$option->id})\n";
            echo "  Option Types:\n";
            
            foreach ($option->optionTypes as $type) {
                echo "    - {$type->type_name} (ID: {$type->id})\n";
                $optionMap[$option->id][] = [
                    'type_id' => $type->id,
                    'type_name' => $type->type_name,
                ];
            }
        }

        // Get existing pricings
        echo "\n=== CURRENT PRICINGS IN DATABASE ===\n";
        $pricings = $orderTemplate->pricings;
        echo "Total pricing entries: " . count($pricings) . "\n\n";
        
        foreach ($pricings as $pricing) {
            echo "- combination_key: '{$pricing->combination_key}' → price: \${$pricing->price}\n";
        }

        // Generate what the JavaScript WILL send
        echo "\n=== WHAT JAVASCRIPT WILL SEND ===\n";
        echo "If user selects one option type from each option:\n\n";
        
        $optionIds = [];
        foreach ($orderTemplate->options as $option) {
            if (count($option->optionTypes) > 0) {
                $firstType = $option->optionTypes->first();
                $optionIds[$option->id] = $firstType->id;
                echo "- Option: {$option->label}\n";
                echo "  Selected: {$firstType->type_name} (type_id: {$firstType->id})\n";
            }
        }

        // Build the combination key the way JavaScript does
        $ids = array_values($optionIds);
        sort($ids, SORT_NUMERIC);
        $jsGeneratedKey = implode(',', $ids);
        
        echo "\nGenerated combination_key: '{$jsGeneratedKey}'\n";

        // Check if this matches any pricing
        echo "\n=== MATCHING ANALYSIS ===\n";
        $found = false;
        foreach ($pricings as $pricing) {
            if ($pricing->combination_key === $jsGeneratedKey) {
                echo "✅ MATCH FOUND!\n";
                echo "Database pricing: $pricing->combination_key\n";
                echo "JavaScript key:   $jsGeneratedKey\n";
                echo "Price: \${$pricing->price}\n";
                $found = true;
                break;
            }
        }

        if (!$found) {
            echo "❌ NO MATCH FOUND!\n";
            echo "Database pricings are using TEXT keys like 'glossy_2x2'\n";
            echo "JavaScript is sending NUMERIC keys like '$jsGeneratedKey'\n";
            echo "\n✓ ROOT CAUSE IDENTIFIED:\n";
            echo "The combination_key format is inconsistent!\n";
            echo "These CANNOT match under any circumstances.\n";
        }
    }

    /**
     * Show what the fix should look like
     */
    public function test_show_corrected_pricings(): void
    {
        echo "\n\n=== CORRECTED PRICINGS (What should be in database) ===\n\n";

        $product = Product::find(1);
        if (!$product) return;

        $orderTemplate = $product->orderTemplate;
        if (!$orderTemplate) return;

        echo "Current pricings (INCORRECT):\n";
        foreach ($orderTemplate->pricings as $pricing) {
            echo "  '{$pricing->combination_key}' => \${$pricing->price}\n";
        }

        echo "\n\nCorrected pricings (CORRECT format):\n";
        echo "Build from actual option type IDs:\n\n";

        // Map option types to their IDs
        $optionTypeMap = [];
        $optionList = [];
        
        foreach ($orderTemplate->options as $option) {
            $optionList[] = $option->label;
            foreach ($option->optionTypes as $type) {
                $optionTypeMap[$type->type_name] = $type->id;
            }
        }

        // Show what the pricings should be
        $corrections = [
            'glossy_2x2' => [$optionTypeMap['Glossy'] ?? '?', $optionTypeMap['2x2 inches'] ?? '?', 0.50],
            'glossy_3x3' => [$optionTypeMap['Glossy'] ?? '?', $optionTypeMap['3x3 inches'] ?? '?', 0.75],
            'glossy_4x4' => [$optionTypeMap['Glossy'] ?? '?', $optionTypeMap['4x4 inches'] ?? '?', 1.25],
            'matte_2x2' => [$optionTypeMap['Matte'] ?? '?', $optionTypeMap['2x2 inches'] ?? '?', 0.60],
            'matte_3x3' => [$optionTypeMap['Matte'] ?? '?', $optionTypeMap['3x3 inches'] ?? '?', 0.85],
            'matte_4x4' => [$optionTypeMap['Matte'] ?? '?', $optionTypeMap['4x4 inches'] ?? '?', 1.35],
            'holographic_2x2' => [$optionTypeMap['Holographic'] ?? '?', $optionTypeMap['2x2 inches'] ?? '?', 1.00],
            'holographic_3x3' => [$optionTypeMap['Holographic'] ?? '?', $optionTypeMap['3x3 inches'] ?? '?', 1.50],
            'holographic_4x4' => [$optionTypeMap['Holographic'] ?? '?', $optionTypeMap['4x4 inches'] ?? '?', 2.00],
        ];

        foreach ($corrections as $oldKey => $data) {
            $finishId = $data[0];
            $sizeId = $data[1];
            $price = $data[2];
            
            $ids = [$finishId, $sizeId];
            sort($ids, SORT_NUMERIC);
            $newKey = implode(',', $ids);
            
            echo "  '{$oldKey}' should be '{$newKey}' => \${$price}\n";
        }
    }
}
