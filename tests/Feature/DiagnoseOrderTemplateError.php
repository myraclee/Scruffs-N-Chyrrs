<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\OrderTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiagnoseOrderTemplateError extends TestCase
{
    /**
     * Test 1: Check if product with ID 1 exists
     */
    public function test_product_exists(): void
    {
        $product = Product::find(1);
        echo "\n=== TEST 1: Product Existence ===\n";
        echo "Product ID 1 exists: " . ($product ? "YES" : "NO") . "\n";
        
        if ($product) {
            echo "Product name: {$product->name}\n";
            echo "Product ID: {$product->id}\n";
        } else {
            echo "ERROR: Product with ID 1 not found in database!\n";
        }
        
        $this->assertTrue($product !== null, 'Product with ID 1 should exist');
    }

    /**
     * Test 2: Check if product has orderTemplate() relationship defined
     */
    public function test_product_has_order_template_method(): void
    {
        echo "\n=== TEST 2: Product has orderTemplate() method ===\n";
        
        $product = Product::find(1);
        
        if ($product) {
            $hasMethod = method_exists($product, 'orderTemplate');
            echo "Product has orderTemplate() method: " . ($hasMethod ? "YES" : "NO") . "\n";
            
            if (!$hasMethod) {
                echo "ERROR: Product model is missing the orderTemplate() relationship!\n";
                echo "Hint: Add this to Product.php:\n";
                echo "    public function orderTemplate(): HasOne\n";
                echo "    {\n";
                echo "        return \$this->hasOne(OrderTemplate::class);\n";
                echo "    }\n";
            }
            
            $this->assertTrue($hasMethod, 'Product should have orderTemplate() method');
        }
    }

    /**
     * Test 3: Try to fetch orderTemplate directly from OrderTemplate table
     */
    public function test_order_template_exists_for_product(): void
    {
        echo "\n=== TEST 3: OrderTemplate exists for Product 1 ===\n";
        
        $orderTemplate = OrderTemplate::where('product_id', 1)->first();
        
        if ($orderTemplate) {
            echo "OrderTemplate found: YES\n";
            echo "OrderTemplate ID: {$orderTemplate->id}\n";
            echo "Product ID: {$orderTemplate->product_id}\n";
        } else {
            echo "ERROR: No OrderTemplate found for product_id = 1!\n";
            echo "This is the root cause of the 500 error.\n";
            echo "Hint: Check if the migration created the order_templates table correctly.\n";
            echo "Hint: Check if there's sample data seeder that creates OrderTemplate for Product 1.\n";
        }
        
        $this->assertTrue($orderTemplate !== null, 'OrderTemplate should exist for product_id 1');
    }

    /**
     * Test 4: Try the exact query the controller is trying to run
     */
    public function test_controller_query_simulation(): void
    {
        echo "\n=== TEST 4: Simulate Controller Query ===\n";
        
        try {
            $product = Product::findOrFail(1);
            echo "✓ Product found\n";
            
            // Try to fetch orderTemplate with all relationships
            $orderTemplate = $product->orderTemplate()
                ->with([
                    'options.optionTypes' => fn($q) => $q->orderBy('position'),
                    'pricings',
                    'discounts',
                    'minOrder',
                    'layoutFee',
                ])
                ->firstOrFail();
            
            echo "✓ OrderTemplate found\n";
            echo "✓ All relationships loaded successfully\n";
            
        } catch (\Exception $e) {
            echo "✗ ERROR: " . $e->getMessage() . "\n";
            echo "Exception type: " . get_class($e) . "\n";
            
            echo "\nDEBUG: Let's check what's actually going wrong:\n";
            
            // Check each relationship separately
            try {
                $product = Product::find(1);
                $orderTemplate = OrderTemplate::where('product_id', 1)->first();
                
                if ($orderTemplate) {
                    echo "  - OrderTemplate exists\n";
                    
                    $optionsCount = $orderTemplate->options()->count();
                    echo "  - Options count: {$optionsCount}\n";
                    
                    $pricingsCount = $orderTemplate->pricings()->count();
                    echo "  - Pricings count: {$pricingsCount}\n";
                    
                    $discountsCount = $orderTemplate->discounts()->count();
                    echo "  - Discounts count: {$discountsCount}\n";
                    
                    $minOrder = $orderTemplate->minOrder()->first();
                    echo "  - MinOrder exists: " . ($minOrder ? "YES" : "NO") . "\n";
                    
                    $layoutFee = $orderTemplate->layoutFee()->first();
                    echo "  - LayoutFee exists: " . ($layoutFee ? "YES" : "NO") . "\n";
                } else {
                    echo "  - OrderTemplate doesn't exist for product_id = 1\n";
                }
            } catch (\Exception $debugEx) {
                echo "  - Debug check failed: " . $debugEx->getMessage() . "\n";
            }
        }
    }

    /**
     * Test 5: Check database tables exist
     */
    public function test_database_tables_exist(): void
    {
        echo "\n=== TEST 5: Database Tables Exist ===\n";
        
        $tables = [
            'products',
            'order_templates',
            'order_template_options',
            'order_template_pricings',
            'order_template_discounts',
            'order_template_min_orders',
            'order_template_layout_fees',
        ];
        
        $schema = app()->make('db')->connection()->getSchemaBuilder();
        
        foreach ($tables as $table) {
            $exists = $schema->hasTable($table);
            echo "{$table}: " . ($exists ? "✓ EXISTS" : "✗ MISSING") . "\n";
        }
    }

    /**
     * Test 6: Call the actual API endpoint to see the error
     */
    public function test_api_endpoint_response(): void
    {
        echo "\n=== TEST 6: API Endpoint Response ===\n";
        
        $response = $this->getJson('/api/customer-orders/product/1/template');
        
        echo "Status code: {$response->status()}\n";
        echo "Response:\n";
        echo json_encode($response->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
        
        if ($response->status() === 500) {
            echo "\n✗ ERROR: API returns 500\n";
            if ($response->json('error')) {
                echo "Error message: " . $response->json('error') . "\n";
            }
        }
    }
}
