<?php

namespace Tests\Feature;

use App\Models\CustomerCart;
use App\Models\CustomerCartItem;
use App\Models\CustomerOrder;
use App\Models\OrderTemplate;
use App\Models\OrderTemplateOption;
use App\Models\OrderTemplateOptionType;
use App\Models\OrderTemplatePricing;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTemplateDeleteConflictTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_soft_deletes_unused_order_template(): void
    {
        $owner = User::factory()->create(['user_type' => 'owner']);
        $fixture = $this->createTemplateFixture('Unused Template Product');

        $response = $this
            ->actingAs($owner)
            ->deleteJson("/api/order-templates/{$fixture['template']->id}");

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Order template deleted successfully');

        $this->assertSoftDeleted('order_templates', [
            'id' => $fixture['template']->id,
        ]);
    }

    public function test_owner_delete_returns_conflict_when_template_is_used_only_by_completed_or_cancelled_orders(): void
    {
        $owner = User::factory()->create(['user_type' => 'owner']);
        $customer = User::factory()->create();
        $fixture = $this->createTemplateFixture('Order Linked Template Product');

        $completedOrder = CustomerOrder::create([
            'customer_order_group_id' => null,
            'user_id' => $customer->id,
            'product_id' => $fixture['product']->id,
            'order_template_id' => $fixture['template']->id,
            'selected_options' => [
                (string) $fixture['option']->id => $fixture['option_type']->id,
            ],
            'quantity' => 1,
            'base_price' => 100,
            'discount_amount' => 0,
            'rush_fee_amount' => 0,
            'layout_fee_amount' => 0,
            'total_price' => 100,
            'status' => 'completed',
        ]);

        CustomerOrder::create([
            'customer_order_group_id' => null,
            'user_id' => $customer->id,
            'product_id' => $fixture['product']->id,
            'order_template_id' => $fixture['template']->id,
            'selected_options' => [
                (string) $fixture['option']->id => $fixture['option_type']->id,
            ],
            'quantity' => 1,
            'base_price' => 100,
            'discount_amount' => 0,
            'rush_fee_amount' => 0,
            'layout_fee_amount' => 0,
            'total_price' => 100,
            'status' => 'cancelled',
        ]);

        $response = $this
            ->actingAs($owner)
            ->deleteJson("/api/order-templates/{$fixture['template']->id}");

        $response
            ->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'order_template_in_use')
            ->assertJsonPath('active_order_count', 2)
            ->assertJsonPath('order_count', 2)
            ->assertJsonPath('total_order_count', 2)
            ->assertJsonPath('cart_item_count', 0);

        $this->assertDatabaseHas('order_templates', [
            'id' => $fixture['template']->id,
            'deleted_at' => null,
        ]);
    }

    public function test_owner_delete_returns_conflict_when_template_is_used_by_active_orders(): void
    {
        $owner = User::factory()->create(['user_type' => 'owner']);
        $customer = User::factory()->create();
        $fixture = $this->createTemplateFixture('Active Order Linked Template Product');

        CustomerOrder::create([
            'customer_order_group_id' => null,
            'user_id' => $customer->id,
            'product_id' => $fixture['product']->id,
            'order_template_id' => $fixture['template']->id,
            'selected_options' => [
                (string) $fixture['option']->id => $fixture['option_type']->id,
            ],
            'quantity' => 1,
            'base_price' => 100,
            'discount_amount' => 0,
            'rush_fee_amount' => 0,
            'layout_fee_amount' => 0,
            'total_price' => 100,
            'status' => 'waiting',
        ]);

        $response = $this
            ->actingAs($owner)
            ->deleteJson("/api/order-templates/{$fixture['template']->id}");

        $response
            ->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'order_template_in_use')
            ->assertJsonPath('active_order_count', 1)
            ->assertJsonPath('order_count', 1)
            ->assertJsonPath('total_order_count', 1)
            ->assertJsonPath('cart_item_count', 0);

        $this->assertDatabaseHas('order_templates', [
            'id' => $fixture['template']->id,
            'deleted_at' => null,
        ]);
    }

    public function test_owner_delete_returns_conflict_when_template_is_used_by_cart_items(): void
    {
        $owner = User::factory()->create(['user_type' => 'owner']);
        $customer = User::factory()->create();
        $fixture = $this->createTemplateFixture('Cart Linked Template Product');

        $cart = CustomerCart::create([
            'user_id' => $customer->id,
        ]);

        CustomerCartItem::create([
            'customer_cart_id' => $cart->id,
            'product_id' => $fixture['product']->id,
            'order_template_id' => $fixture['template']->id,
            'rush_fee_id' => null,
            'selected_options' => [
                (string) $fixture['option']->id => $fixture['option_type']->id,
            ],
            'quantity' => 2,
            'special_instructions' => null,
            'base_price' => 100,
            'discount_amount' => 0,
            'rush_fee_amount' => 0,
            'layout_fee_amount' => 0,
            'total_price' => 200,
        ]);

        $response = $this
            ->actingAs($owner)
            ->deleteJson("/api/order-templates/{$fixture['template']->id}");

        $response
            ->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'order_template_in_use')
            ->assertJsonPath('active_order_count', 0)
            ->assertJsonPath('order_count', 0)
            ->assertJsonPath('total_order_count', 0)
            ->assertJsonPath('cart_item_count', 1);

        $this->assertDatabaseHas('order_templates', [
            'id' => $fixture['template']->id,
            'deleted_at' => null,
        ]);
    }

    /**
     * @return array{product: Product, template: OrderTemplate, option: OrderTemplateOption, option_type: OrderTemplateOptionType}
     */
    private function createTemplateFixture(string $name): array
    {
        $product = Product::create([
            'name' => $name,
            'description' => 'Fixture product description',
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

        return [
            'product' => $product,
            'template' => $template,
            'option' => $option,
            'option_type' => $optionType,
        ];
    }
}
