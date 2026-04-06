<?php

namespace Tests\Feature;

use App\Models\CustomerOrder;
use App\Models\CustomerOrderGroup;
use App\Models\Material;
use App\Models\MaterialConsumption;
use App\Models\OrderTemplate;
use App\Models\OrderTemplateOption;
use App\Models\OrderTemplateOptionType;
use App\Models\OrderTemplatePricing;
use App\Models\Product;
use App\Models\RushFee;
use App\Models\RushFeeTimeframe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerOrderEditDetailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_edit_waiting_order_group_with_repricing_and_inventory_delta(): void
    {
        $customer = User::factory()->create();
        $fixture = $this->createTemplateFixture('Editable Waiting Product');

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
            'name' => 'Customer Editable Material '.uniqid(),
            'units' => 48,
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
            'general_drive_link' => 'https://drive.google.com/drive/folders/original-customer-link',
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
            ->actingAs($customer)
            ->patchJson("/api/customer-orders/{$group->id}/details", [
                'general_drive_link' => 'https://drive.google.com/drive/folders/updated-customer-link',
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
            ->assertJsonPath('data.status', 'waiting')
            ->assertJsonPath('data.is_editable', true)
            ->assertJsonPath('data.orders.0.quantity', 4)
            ->assertJsonPath('data.orders.0.rush_fee_id', $rushFee->id)
            ->assertJsonPath('data.totals.subtotal_price', 480)
            ->assertJsonPath('data.totals.rush_fee_total', 48)
            ->assertJsonPath('data.totals.layout_fee_total', 0)
            ->assertJsonPath('data.totals.total_price', 528);

        $this->assertDatabaseHas('customer_order_groups', [
            'id' => $group->id,
            'general_drive_link' => 'https://drive.google.com/drive/folders/updated-customer-link',
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
        $this->assertSame(46, (int) $material->units);
    }

    public function test_customer_cannot_edit_non_waiting_group(): void
    {
        $customer = User::factory()->create();
        $fixture = $this->createTemplateFixture('Locked Customer Group Product');

        $group = CustomerOrderGroup::create([
            'user_id' => $customer->id,
            'status' => 'approved',
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
            'status' => 'approved',
        ]);

        $response = $this
            ->actingAs($customer)
            ->patchJson("/api/customer-orders/{$group->id}/details", [
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
            ->assertJsonPath('error_code', 'customer_order_not_editable');
    }

    public function test_customer_cannot_edit_another_users_order_group(): void
    {
        $groupOwner = User::factory()->create();
        $otherCustomer = User::factory()->create();
        $fixture = $this->createTemplateFixture('Forbidden Edit Product');

        $group = CustomerOrderGroup::create([
            'user_id' => $groupOwner->id,
            'status' => 'waiting',
            'total_price' => 100,
        ]);

        $order = CustomerOrder::create([
            'customer_order_group_id' => $group->id,
            'user_id' => $groupOwner->id,
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
            ->actingAs($otherCustomer)
            ->patchJson("/api/customer-orders/{$group->id}/details", [
                'orders' => [[
                    'id' => $order->id,
                    'selected_options' => [
                        (string) $fixture['option']->id => $fixture['option_type']->id,
                    ],
                    'quantity' => 1,
                    'rush_fee_id' => null,
                    'special_instructions' => null,
                ]],
            ]);

        $response
            ->assertForbidden()
            ->assertJsonPath('success', false);
    }

    public function test_customer_edit_rejects_invalid_drive_link(): void
    {
        $customer = User::factory()->create();
        $fixture = $this->createTemplateFixture('Drive Link Validation Product');

        $group = CustomerOrderGroup::create([
            'user_id' => $customer->id,
            'status' => 'waiting',
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

        $response = $this
            ->actingAs($customer)
            ->patchJson("/api/customer-orders/{$group->id}/details", [
                'general_drive_link' => 'https://example.com/not-drive-link',
                'orders' => [[
                    'id' => $order->id,
                    'selected_options' => [
                        (string) $fixture['option']->id => $fixture['option_type']->id,
                    ],
                    'quantity' => 1,
                    'rush_fee_id' => null,
                    'special_instructions' => null,
                ]],
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['general_drive_link']);
    }

    /**
     * @return array{product: Product, template: OrderTemplate, option: OrderTemplateOption, option_type: OrderTemplateOptionType, option_type_alt: OrderTemplateOptionType}
     */
    private function createTemplateFixture(string $productName): array
    {
        $product = Product::create([
            'name' => $productName,
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

        return [
            'product' => $product,
            'template' => $template,
            'option' => $option,
            'option_type' => $optionType,
            'option_type_alt' => $optionTypeAlt,
        ];
    }
}
