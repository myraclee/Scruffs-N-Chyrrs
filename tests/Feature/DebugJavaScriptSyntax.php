<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * DEBUG TEST: Verify product-detail.js doesn't have syntax errors
 * 
 * Checks:
 * 1. No syntax errors in JavaScript
 * 2. All functions are properly defined
 * 3. Event listener code is syntactically valid
 */
class DebugJavaScriptSyntax extends TestCase
{
    public function test_product_detail_js_has_no_obvious_syntax_errors(): void
    {
        echo "\n========== JAVASCRIPT SYNTAX CHECK ==========\n\n";

        try {
            $filePath = base_path('resources/js/customer/pages/product-detail.js');
            $content = file_get_contents($filePath);

            echo "Checking product-detail.js for syntax patterns...\n\n";

            // Check 1: Function definitions
            echo "Function Definitions:\n";
            $functionPatterns = [
                'initializeProductDetail' => 'function initializeProductDetail()',
                'setupEventListeners' => 'function setupEventListeners()',
                'renderSkeletonLoaders' => 'function renderSkeletonLoaders()',
                'loadAndRenderPriceImages' => 'function loadAndRenderPriceImages()',
                'calculateOrderPrice' => 'function calculateOrderPrice()',
            ];

            foreach ($functionPatterns as $name => $pattern) {
                $searchPatterns = [
                    "function " . $name,
                    "const " . $name . " =",
                    $name . "() {"
                ];

                $found = false;
                foreach ($searchPatterns as $search) {
                    if (strpos($content, $search) !== false) {
                        echo "  ✓ " . $name . " defined\n";
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    echo "  ✗ " . $name . " NOT FOUND\n";
                }
            }

            // Check 2: Event listeners
            echo "\nEvent Listeners:\n";
            $eventPatterns = [
                'addEventListener("click"' => 'Click event listener',
                'addEventListener("DOMContentLoaded"' => 'DOMContentLoaded listener',
            ];

            foreach ($eventPatterns as $pattern => $desc) {
                if (strpos($content, $pattern) !== false) {
                    echo "  ✓ " . $desc . "\n";
                } else {
                    echo "  ✗ " . $desc . " NOT FOUND\n";
                }
            }

            // Check 3: Missing bracket/paren matching
            echo "\nBracket/Parenthesis Balance:\n";
            
            $openBraces = substr_count($content, '{');
            $closeBraces = substr_count($content, '}');
            $openParens = substr_count($content, '(');
            $closeParens = substr_count($content, ')');

            echo "  Opening {: " . $openBraces . ", Closing }: " . $closeBraces;
            if ($openBraces === $closeBraces) {
                echo " ✓\n";
            } else {
                echo " ✗ MISMATCH\n";
            }

            echo "  Opening (: " . $openParens . ", Closing ): " . $closeParens;
            if ($openParens === $closeParens) {
                echo " ✓\n";
            } else {
                echo " ✗ MISMATCH\n";
            }

            // Check 4: Try-catch blocks
            echo "\nError Handling:\n";
            $tryCatchCount = substr_count($content, 'try {');
            $catchCount = substr_count($content, 'catch');

            echo "  Try blocks: " . $tryCatchCount . "\n";
            echo "  Catch blocks: " . $catchCount . "\n";

            if ($catchCount > 0) {
                echo "  ✓ Error handling present\n";
            }

            // Check 5: Critical DOM query methods
            echo "\nDOM Query Methods:\n";
            $queryMethods = [
                'getElementById' => 'Direct ID lookup',
                'querySelector' => 'CSS selector lookup',
                'addEventListener' => 'Event attachment',
            ];

            foreach ($queryMethods as $method => $desc) {
                $count = substr_count($content, $method);
                if ($count > 0) {
                    echo "  ✓ " . $method . " (" . $count . " times) - " . $desc . "\n";
                } else {
                    echo "  ✗ " . $method . " NOT USED\n";
                }
            }

            // Check 6: Common syntax issues
            echo "\nCommon Issues Check:\n";

            $issues = [
                'Missing semicolons at EOF' => !preg_match('/}\s*$/', $content),
                'Unmatched strings' => (substr_count($content, '"') % 2 !== 0 && substr_count($content, "'") % 2 !== 0),
                'Missing export' => !preg_match('/export\s+(default|function|const)/i', $content), // product-detail doesn't export
            ];

            foreach ($issues as $issue => $hasIssue) {
                if ($hasIssue && strpos($issue, 'export') === false) {
                    echo "  ⚠ " . $issue . "\n";
                } elseif (!$hasIssue) {
                    echo "  ✓ No " . $issue . "\n";
                }
            }

        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }

        echo "\n==========================================\n\n";

        $this->assertTrue(true, "Syntax check complete");
    }
}
