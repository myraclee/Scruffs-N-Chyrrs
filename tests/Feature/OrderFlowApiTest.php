<?php

namespace Tests\Feature;

use App\Models\CustomerOrder;
use App\Models\CustomerOrderGroup;
use App\Models\OrderTemplate;
use App\Models\OrderTemplateLayoutFee;
use App\Models\OrderTemplateMinOrder;
use App\Models\OrderTemplateOption;
use App\Models\OrderTemplateOptionType;
use App\Models\OrderTemplatePricing;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderFlowApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_add_cart_items_and_checkout_grouped_order(): void
    {
        $customer = User::factory()->create();
        $fixture = $this->createTemplateFixture();

        $this->actingAs($customer);

        $addResponse = $this->postJson('/api/customer-cart/items', [
            'product_id' => $fixture['product']->id,
            'order_template_id' => $fixture['template']->id,
            'selected_options' => [
                (string) $fixture['option']->id => $fixture['option_type']->id,
            ],
            'quantity' => 2,
            'special_instructions' => 'front_logo.png',
        ]);

        $addResponse
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.item_count', 1)
            ->assertJsonPath('data.items.0.quantity', 2)
            ->assertJsonPath('data.items.0.total_price', 200);

        $this->assertDatabaseCount('customer_cart_items', 1);

        $checkoutResponse = $this->postJson('/api/customer-cart/checkout', [
            'general_drive_link' => 'https://drive.google.com/order-folder',
        ]);

        $checkoutResponse
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'waiting')
            ->assertJsonPath('data.total_price', 200);

        $groupId = $checkoutResponse->json('data.order_group_id');

        $this->assertDatabaseHas('customer_order_groups', [
            'id' => $groupId,
            'user_id' => $customer->id,
            'status' => 'waiting',
        ]);

        $this->assertDatabaseHas('customer_orders', [
            'customer_order_group_id' => $groupId,
            'user_id' => $customer->id,
            'product_id' => $fixture['product']->id,
            'order_template_id' => $fixture['template']->id,
            'quantity' => 2,
            'status' => 'waiting',
        ]);

        $this->assertDatabaseCount('customer_cart_items', 0);

        $ordersResponse = $this->getJson('/api/customer-orders');

        $ordersResponse
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.id', $groupId)
            ->assertJsonPath('data.0.items_count', 1)
            ->assertJsonPath('data.0.status', 'waiting');
    }

    public function test_customer_cannot_view_another_users_grouped_order(): void
    {
        $groupOwner = User::factory()->create();
        $otherCustomer = User::factory()->create();

        $group = CustomerOrderGroup::create([
            'user_id' => $groupOwner->id,
            'status' => 'waiting',
            'total_price' => 100,
        ]);

        $response = $this
            ->actingAs($otherCustomer)
            ->getJson("/api/customer-orders/{$group->id}");

        $response
            ->assertForbidden()
            ->assertJsonPath('success', false);
    }

    public function test_owner_can_view_details_and_update_grouped_order_status_with_transition_rules(): void
    {
        $owner = User::factory()->create(['user_type' => 'owner']);
        $customer = User::factory()->create();
        $fixture = $this->createTemplateFixture('Owner Fixture Product');

        $group = CustomerOrderGroup::create([
            'user_id' => $customer->id,
            'status' => 'waiting',
            'general_drive_link' => 'https://drive.google.com/sample-owner-order',
            'subtotal_price' => 100,
            'discount_total' => 0,
            'rush_fee_total' => 0,
            'layout_fee_total' => 0,
            'total_price' => 100,
        ]);

        $order = CustomerOrder::create([
            'customer_order_group_id' => $group->id,
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

        $detailsResponse = $this
            ->actingAs($owner)
            ->getJson("/api/owner/orders/{$group->id}");

        $detailsResponse
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $group->id)
            ->assertJsonPath('data.orders.0.id', $order->id)
            ->assertJsonPath('data.orders.0.formatted_options.0.option_label', 'Finish')
            ->assertJsonPath('data.orders.0.formatted_options.0.selected_value', 'Matte');

        $invalidTransitionResponse = $this
            ->actingAs($owner)
            ->patchJson("/api/owner/orders/{$group->id}/status", [
                'status' => 'ready',
            ]);

        $invalidTransitionResponse
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $validTransitionResponse = $this
            ->actingAs($owner)
            ->patchJson("/api/owner/orders/{$group->id}/status", [
                'status' => 'approved',
            ]);

        $validTransitionResponse
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'approved');

        $this->assertDatabaseHas('customer_order_groups', [
            'id' => $group->id,
            'status' => 'approved',
        ]);

        $this->assertDatabaseHas('customer_orders', [
            'id' => $order->id,
            'status' => 'approved',
        ]);
    }

    public function test_non_owner_receives_forbidden_response_from_owner_order_api(): void
    {
        $customer = User::factory()->create();

        $response = $this
            ->actingAs($customer)
            ->getJson('/api/owner/orders');

        $response
            ->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Unauthorized access.');
    }

    /**
     * @return array{product: Product, template: OrderTemplate, option: OrderTemplateOption, option_type: OrderTemplateOptionType}
     */
    private function createTemplateFixture(string $name = 'Test Product'): array
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

        OrderTemplateMinOrder::create([
            'order_template_id' => $template->id,
            'min_quantity' => 1,
        ]);

        OrderTemplateLayoutFee::create([
            'order_template_id' => $template->id,
            'fee_amount' => 0,
        ]);

        return [
            'product' => $product,
            'template' => $template,
            'option' => $option,
            'option_type' => $optionType,
        ];
    }
}
