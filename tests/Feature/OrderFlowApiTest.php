<?php

namespace Tests\Feature;

use App\Models\CustomerOrder;
use App\Models\CustomerOrderGroup;
use App\Models\Material;
use App\Models\MaterialConsumption;
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

        $rushFee = RushFee::create([
            'label' => 'Express Processing',
            'image_url' => 'uploads/rush-express.png',
            'min_price' => 0,
            'max_price' => 100000,
        ]);

        RushFeeTimeframe::create([
            'rush_fee_id' => $rushFee->id,
            'label' => '48 hours',
            'percentage' => 12,
            'sort_order' => 1,
        ]);

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
            ->assertJsonPath('data.orders.0.formatted_options.0.selected_value', 'Matte')
            ->assertJsonPath('data.orders.0.option_schema.0.label', 'Finish')
            ->assertJsonPath('data.orders.0.min_order_quantity', 1)
            ->assertJsonPath('data.rush_fee_options.0.id', $rushFee->id)
            ->assertJsonPath('data.rush_fee_options.0.timeframes.0.percentage', 12);

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

    public function test_owner_can_edit_order_details_and_drive_link_with_repricing_and_inventory_delta(): void
    {
        $owner = User::factory()->create(['user_type' => 'owner']);
        $customer = User::factory()->create();
        $fixture = $this->createTemplateFixture('Editable Product');

        $rushFee = RushFee::create([
            'label' => 'Priority Window',
            'image_url' => 'uploads/rush-priority.png',
            'min_price' => 0,
            'max_price' => 100000,
        ]);

        RushFeeTimeframe::create([
            'rush_fee_id' => $rushFee->id,
            'label' => '24 hours',
            'percentage' => 10,
            'sort_order' => 1,
        ]);

        $material = Material::create([
            'name' => 'Editable Material '.uniqid(),
            'units' => 18,
        ]);

        MaterialConsumption::create([
            'material_id' => $material->id,
            'product_id' => $fixture['product']->id,
            'order_template_option_type_id' => null,
            'quantity' => 1,
        ]);

        $group = CustomerOrderGroup::create([
            'user_id' => $customer->id,
            'status' => 'waiting',
            'general_drive_link' => 'https://drive.google.com/original-link',
            'subtotal_price' => 200,
            'discount_total' => 0,
            'rush_fee_total' => 0,
            'layout_fee_total' => 0,
            'total_price' => 200,
            'inventory_material_requirements' => [[
                'material_id' => $material->id,
                'material_name' => $material->name,
                'required' => 2,
                'breakdown' => [],
            ]],
            'inventory_deducted_at' => now(),
        ]);

        $order = CustomerOrder::create([
            'customer_order_group_id' => $group->id,
            'user_id' => $customer->id,
            'product_id' => $fixture['product']->id,
            'order_template_id' => $fixture['template']->id,
            'selected_options' => [
                (string) $fixture['option']->id => $fixture['option_type']->id,
            ],
            'quantity' => 2,
            'special_instructions' => null,
            'base_price' => 200,
            'discount_amount' => 0,
            'rush_fee_amount' => 0,
            'layout_fee_amount' => 0,
            'total_price' => 200,
            'status' => 'waiting',
        ]);

        $response = $this
            ->actingAs($owner)
            ->patchJson("/api/owner/orders/{$group->id}/details", [
                'general_drive_link' => 'https://drive.google.com/updated-link',
                'orders' => [[
                    'id' => $order->id,
                    'selected_options' => [
                        (string) $fixture['option']->id => $fixture['option_type_alt']->id,
                    ],
                    'quantity' => 4,
                    'rush_fee_id' => $rushFee->id,
                    'special_instructions' => 'front.png,back.png',
                ]],
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.general_drive_link', 'https://drive.google.com/updated-link')
            ->assertJsonPath('data.orders.0.quantity', 4)
            ->assertJsonPath('data.orders.0.rush_fee_id', $rushFee->id)
            ->assertJsonPath('data.orders.0.special_instructions', 'front.png,back.png')
            ->assertJsonPath('data.totals.subtotal_price', 480)
            ->assertJsonPath('data.totals.rush_fee_total', 48)
            ->assertJsonPath('data.totals.layout_fee_total', 0)
            ->assertJsonPath('data.totals.total_price', 528);

        $this->assertDatabaseHas('customer_order_groups', [
            'id' => $group->id,
            'general_drive_link' => 'https://drive.google.com/updated-link',
            'subtotal_price' => 480.00,
            'rush_fee_total' => 48.00,
            'layout_fee_total' => 0.00,
            'total_price' => 528.00,
        ]);

        $this->assertDatabaseHas('customer_orders', [
            'id' => $order->id,
            'quantity' => 4,
            'rush_fee_id' => $rushFee->id,
            'special_instructions' => 'front.png,back.png',
            'base_price' => 480.00,
            'rush_fee_amount' => 48.00,
            'layout_fee_amount' => 0.00,
            'total_price' => 528.00,
        ]);

        $material->refresh();
        $this->assertSame(16, (int) $material->units);
    }

    public function test_owner_cannot_edit_details_for_completed_or_cancelled_orders(): void
    {
        $owner = User::factory()->create(['user_type' => 'owner']);
        $customer = User::factory()->create();
        $fixture = $this->createTemplateFixture('Locked Group Product');

        $group = CustomerOrderGroup::create([
            'user_id' => $customer->id,
            'status' => 'completed',
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
            'status' => 'completed',
        ]);

        $response = $this
            ->actingAs($owner)
            ->patchJson("/api/owner/orders/{$group->id}/details", [
                'general_drive_link' => 'https://drive.google.com/should-not-save',
                'orders' => [[
                    'id' => $order->id,
                    'selected_options' => [
                        (string) $fixture['option']->id => $fixture['option_type']->id,
                    ],
                    'quantity' => 2,
                    'rush_fee_id' => null,
                    'special_instructions' => null,
                ]],
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Completed or cancelled orders cannot be edited.');
    }

    public function test_owner_edit_blocks_when_inventory_delta_causes_shortage(): void
    {
        $owner = User::factory()->create(['user_type' => 'owner']);
        $customer = User::factory()->create();
        $fixture = $this->createTemplateFixture('Shortage Product');

        $material = Material::create([
            'name' => 'Shortage Material '.uniqid(),
            'units' => 1,
        ]);

        MaterialConsumption::create([
            'material_id' => $material->id,
            'product_id' => $fixture['product']->id,
            'order_template_option_type_id' => null,
            'quantity' => 1,
        ]);

        $group = CustomerOrderGroup::create([
            'user_id' => $customer->id,
            'status' => 'waiting',
            'subtotal_price' => 100,
            'discount_total' => 0,
            'rush_fee_total' => 0,
            'layout_fee_total' => 0,
            'total_price' => 100,
            'inventory_material_requirements' => [[
                'material_id' => $material->id,
                'material_name' => $material->name,
                'required' => 1,
                'breakdown' => [],
            ]],
            'inventory_deducted_at' => now(),
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

        $response = $this
            ->actingAs($owner)
            ->patchJson("/api/owner/orders/{$group->id}/details", [
                'orders' => [[
                    'id' => $order->id,
                    'selected_options' => [
                        (string) $fixture['option']->id => $fixture['option_type']->id,
                    ],
                    'quantity' => 4,
                    'rush_fee_id' => null,
                    'special_instructions' => null,
                ]],
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('shortages.0.material_id', $material->id)
            ->assertJsonPath('shortages.0.required', 3)
            ->assertJsonPath('shortages.0.available', 1)
            ->assertJsonPath('shortages.0.deficit', 2);

        $this->assertDatabaseHas('customer_orders', [
            'id' => $order->id,
            'quantity' => 1,
            'total_price' => 100.00,
        ]);

        $material->refresh();
        $this->assertSame(1, (int) $material->units);
    }

    /**
     * @return array{product: Product, template: OrderTemplate, option: OrderTemplateOption, option_type: OrderTemplateOptionType, option_type_alt: OrderTemplateOptionType}
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

        $optionTypeAlt = OrderTemplateOptionType::create([
            'order_template_option_id' => $option->id,
            'type_name' => 'Glossy',
            'is_available' => true,
            'position' => 2,
        ]);

        OrderTemplatePricing::create([
            'order_template_id' => $template->id,
            'combination_key' => (string) $optionType->id,
            'price' => 100,
        ]);

        OrderTemplatePricing::create([
            'order_template_id' => $template->id,
            'combination_key' => (string) $optionTypeAlt->id,
            'price' => 120,
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
            'option_type_alt' => $optionTypeAlt,
        ];
    }
}
