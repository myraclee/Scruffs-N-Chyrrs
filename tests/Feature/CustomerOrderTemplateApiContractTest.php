<?php

namespace Tests\Feature;

use App\Models\OrderTemplate;
use App\Models\OrderTemplateOption;
use App\Models\OrderTemplateOptionType;
use App\Models\Material;
use App\Models\MaterialConsumption;
use App\Models\Product;
use App\Models\RushFee;
use App\Models\RushFeeTimeframe;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerOrderTemplateApiContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_200_when_product_template_exists(): void
    {
        $product = Product::create([
            'name' => 'Configured Product',
            'slug' => 'configured-product',
            'description' => 'Configured for order template contract testing.',
            'cover_image_path' => 'products/covers/configured.jpg',
        ]);

        OrderTemplate::create([
            'product_id' => $product->id,
        ]);

        $response = $this->getJson("/api/customer-orders/product/{$product->id}/template");

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.product.id', $product->id)
            ->assertJsonPath('data.inventory.buffer_rule', 'allow_at_threshold_block_below')
            ->assertJsonPath('data.inventory.max_order_quantity', null);
    }

    public function test_returns_404_when_product_does_not_exist(): void
    {
        $response = $this->getJson('/api/customer-orders/product/999999/template');

        $response
            ->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'product_not_found');
    }

    public function test_returns_404_when_product_has_no_order_template_configured(): void
    {
        $product = Product::create([
            'name' => 'No Template Product',
            'slug' => 'no-template-product',
            'description' => 'Used for contract testing.',
            'cover_image_path' => 'products/covers/no-template.jpg',
        ]);

        $response = $this->getJson("/api/customer-orders/product/{$product->id}/template");

        $response
            ->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'template_not_configured');
    }

    public function test_preserves_null_max_price_for_open_ended_rush_fee_in_template_payload(): void
    {
        $product = Product::create([
            'name' => 'Open Ended Rush Fee Product',
            'slug' => 'open-ended-rush-fee-product',
            'description' => 'Configured product with open-ended rush fee.',
            'cover_image_path' => 'products/covers/open-ended.jpg',
        ]);

        OrderTemplate::create([
            'product_id' => $product->id,
        ]);

        $rushFee = RushFee::create([
            'label' => 'Above 5000',
            'min_price' => 5000,
            'max_price' => null,
            'image_url' => '',
        ]);

        RushFeeTimeframe::create([
            'rush_fee_id' => $rushFee->id,
            'label' => '24 hours',
            'percentage' => 25,
            'sort_order' => 0,
        ]);

        $response = $this->getJson("/api/customer-orders/product/{$product->id}/template");

        $response->assertOk();

        $rushFees = $response->json('data.rush_fees');

        $this->assertIsArray($rushFees);

        $matchingRushFee = collect($rushFees)->firstWhere('id', $rushFee->id);

        $this->assertNotNull($matchingRushFee);
        $this->assertArrayHasKey('max_price', $matchingRushFee);
        $this->assertNull($matchingRushFee['max_price']);
    }

    public function test_template_inventory_payload_returns_max_order_quantity_for_selected_option_context(): void
    {
        $product = Product::create([
            'name' => 'Inventory Limit Product',
            'slug' => 'inventory-limit-product',
            'description' => 'Configured product with selected-option inventory limit.',
            'cover_image_path' => 'products/covers/inventory-limit.jpg',
        ]);

        $template = OrderTemplate::create([
            'product_id' => $product->id,
        ]);

        $option = OrderTemplateOption::create([
            'order_template_id' => $template->id,
            'label' => 'Finish',
            'position' => 1,
        ]);

        $optionType = OrderTemplateOptionType::create([
            'order_template_option_id' => $option->id,
            'type_name' => 'Matte',
            'is_available' => true,
            'position' => 1,
        ]);

        $material = Material::create([
            'name' => 'Inventory Limit Material '.uniqid(),
            'units' => 55,
            'low_stock_threshold' => 5,
        ]);

        MaterialConsumption::create([
            'material_id' => $material->id,
            'product_id' => $product->id,
            'order_template_option_type_id' => $optionType->id,
            'quantity' => 1,
        ]);

        $response = $this->getJson(
            "/api/customer-orders/product/{$product->id}/template?selected_option_type_ids[]={$optionType->id}"
        );

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.inventory.max_order_quantity', 50)
            ->assertJsonPath('data.inventory.selected_option_type_ids.0', $optionType->id);
    }
}
