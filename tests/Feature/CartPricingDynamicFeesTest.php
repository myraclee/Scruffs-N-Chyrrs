<?php

namespace Tests\Feature;

use App\Models\CustomerCart;
use App\Models\CustomerCartItem;
use App\Models\OrderTemplate;
use App\Models\OrderTemplateLayoutFee;
use App\Models\OrderTemplateMinOrder;
use App\Models\OrderTemplateOption;
use App\Models\OrderTemplateOptionType;
use App\Models\OrderTemplatePricing;
use App\Models\Product;
use App\Models\RushFee;
use App\Models\RushFeeTimeframe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartPricingDynamicFeesTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_item_uses_layout_count_and_rush_fee_in_total_calculation(): void
    {
        $customer = User::factory()->create();
        $fixture = $this->createPricingFixture();

        $response = $this
            ->actingAs($customer)
            ->postJson('/api/customer-cart/items', [
                'product_id' => $fixture['product']->id,
                'order_template_id' => $fixture['template']->id,
                'selected_options' => [
                    (string) $fixture['option']->id => $fixture['option_type']->id,
                ],
                'quantity' => 2,
                'rush_fee_id' => $fixture['rush_fee']->id,
                'special_instructions' => 'layout-one, , layout-two, layout-three',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.items.0.base_price', 200)
            ->assertJsonPath('data.items.0.layout_fee_amount', 30)
            ->assertJsonPath('data.items.0.rush_fee_amount', 23)
            ->assertJsonPath('data.items.0.total_price', 253)
            ->assertJsonPath('data.totals.layout_fee_amount', 30)
            ->assertJsonPath('data.totals.rush_fee_amount', 23)
            ->assertJsonPath('data.totals.total_price', 253);
    }

    public function test_direct_order_endpoint_matches_cart_pricing_contract(): void
    {
        $customer = User::factory()->create();
        $fixture = $this->createPricingFixture('Direct Order Product');

        $response = $this
            ->actingAs($customer)
            ->postJson('/api/customer-orders', [
                'product_id' => $fixture['product']->id,
                'order_template_id' => $fixture['template']->id,
                'selected_options' => [
                    (string) $fixture['option']->id => $fixture['option_type']->id,
                ],
                'quantity' => 2,
                'rush_fee_id' => $fixture['rush_fee']->id,
                'special_instructions' => 'layout-one,layout-two,layout-three',
                'general_drive_link' => 'https://drive.google.com/direct-order-folder',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_price', 253);

        $this->assertDatabaseHas('customer_orders', [
            'user_id' => $customer->id,
            'product_id' => $fixture['product']->id,
            'layout_fee_amount' => 30.00,
            'rush_fee_amount' => 23.00,
            'total_price' => 253.00,
        ]);

        $this->assertDatabaseHas('customer_order_groups', [
            'user_id' => $customer->id,
            'layout_fee_total' => 30.00,
            'rush_fee_total' => 23.00,
            'total_price' => 253.00,
        ]);
    }

    public function test_cart_index_recalculates_stale_existing_item_totals(): void
    {
        $customer = User::factory()->create();
        $fixture = $this->createPricingFixture('Recalc Index Product');

        $cart = CustomerCart::forUser((int) $customer->id);

        $cartItem = CustomerCartItem::create([
            'customer_cart_id' => $cart->id,
            'product_id' => $fixture['product']->id,
            'order_template_id' => $fixture['template']->id,
            'rush_fee_id' => $fixture['rush_fee']->id,
            'selected_options' => [
                (string) $fixture['option']->id => $fixture['option_type']->id,
            ],
            'quantity' => 2,
            'special_instructions' => 'layout-one,layout-two,layout-three',
            'base_price' => 200,
            'discount_amount' => 0,
            'rush_fee_amount' => 0,
            'layout_fee_amount' => 0,
            'total_price' => 200,
        ]);

        $response = $this
            ->actingAs($customer)
            ->getJson('/api/customer-cart');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.items.0.layout_fee_amount', 30)
            ->assertJsonPath('data.items.0.rush_fee_amount', 23)
            ->assertJsonPath('data.items.0.total_price', 253);

        $this->assertDatabaseHas('customer_cart_items', [
            'id' => $cartItem->id,
            'layout_fee_amount' => 30.00,
            'rush_fee_amount' => 23.00,
            'total_price' => 253.00,
        ]);
    }

    public function test_cart_update_and_checkout_recalculate_stale_item_totals(): void
    {
        $customer = User::factory()->create();
        $fixture = $this->createPricingFixture('Recalc Update Product');

        $cart = CustomerCart::forUser((int) $customer->id);

        $cartItem = CustomerCartItem::create([
            'customer_cart_id' => $cart->id,
            'product_id' => $fixture['product']->id,
            'order_template_id' => $fixture['template']->id,
            'rush_fee_id' => $fixture['rush_fee']->id,
            'selected_options' => [
                (string) $fixture['option']->id => $fixture['option_type']->id,
            ],
            'quantity' => 2,
            'special_instructions' => 'layout-one,layout-two,layout-three',
            'base_price' => 200,
            'discount_amount' => 0,
            'rush_fee_amount' => 0,
            'layout_fee_amount' => 0,
            'total_price' => 200,
        ]);

        $updateResponse = $this
            ->actingAs($customer)
            ->patchJson("/api/customer-cart/items/{$cartItem->id}", [
                'quantity' => 2,
            ]);

        $updateResponse
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.items.0.layout_fee_amount', 30)
            ->assertJsonPath('data.items.0.rush_fee_amount', 23)
            ->assertJsonPath('data.items.0.total_price', 253);

        $checkoutResponse = $this
            ->actingAs($customer)
            ->postJson('/api/customer-cart/checkout', [
                'general_drive_link' => 'https://drive.google.com/cart-checkout-folder',
            ]);

        $checkoutResponse
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_price', 253);

        $this->assertDatabaseHas('customer_order_groups', [
            'user_id' => $customer->id,
            'layout_fee_total' => 30.00,
            'rush_fee_total' => 23.00,
            'total_price' => 253.00,
        ]);
    }

    /**
     * @return array{
     *     product: Product,
     *     template: OrderTemplate,
     *     option: OrderTemplateOption,
     *     option_type: OrderTemplateOptionType,
     *     rush_fee: RushFee
     * }
     */
    private function createPricingFixture(string $name = 'Dynamic Pricing Product'): array
    {
        $product = Product::create([
            'name' => $name,
            'description' => 'Fixture product for dynamic pricing tests',
            'cover_image_path' => '/images/fixture.png',
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

        OrderTemplatePricing::create([
            'order_template_id' => $template->id,
            'combination_key' => (string) $optionType->id,
            'price' => 100,
        ]);

        OrderTemplateMinOrder::create([
            'order_template_id' => $template->id,
            'min_quantity' => 1,
        ]);

        OrderTemplateLayoutFee::create([
            'order_template_id' => $template->id,
            'fee_amount' => 10,
        ]);

        $rushFee = RushFee::create([
            'label' => 'Rush Processing',
            'min_price' => 0,
            'max_price' => 999999,
            'image_url' => '/images/rush/default.png',
        ]);

        RushFeeTimeframe::create([
            'rush_fee_id' => $rushFee->id,
            'label' => '2 Days',
            'percentage' => 10,
            'sort_order' => 1,
        ]);

        return [
            'product' => $product,
            'template' => $template,
            'option' => $option,
            'option_type' => $optionType,
            'rush_fee' => $rushFee,
        ];
    }
}
