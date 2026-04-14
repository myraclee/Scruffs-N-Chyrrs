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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OrderFlowApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_add_cart_items_and_checkout_grouped_order(): void
    {
        $customer = User::factory()->create();
        $fixture = $this->createTemplateFixture();

        $material = Material::create([
            'name' => 'Grouped Checkout Material '.uniqid(),
            'units' => 50,
        ]);

        MaterialConsumption::create([
            'material_id' => $material->id,
            'product_id' => $fixture['product']->id,
            'order_template_option_type_id' => $fixture['option_type']->id,
            'quantity' => 1,
        ]);

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
            'general_drive_link' => 'https://drive.google.com/drive/folders/order-folder',
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

    public function test_checkout_rejects_invalid_main_drive_link(): void
    {
        $customer = User::factory()->create();

        $response = $this
            ->actingAs($customer)
            ->postJson('/api/customer-cart/checkout', [
                'general_drive_link' => 'https://example.com/not-drive-link',
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['general_drive_link']);
    }

    public function test_checkout_returns_schema_mismatch_payload_when_payment_columns_are_missing(): void
    {
        $customer = User::factory()->create();
        $fixture = $this->createTemplateFixture('Schema Drift Checkout Product');

        $material = Material::create([
            'name' => 'Schema Drift Material '.uniqid(),
            'units' => 25,
        ]);

        MaterialConsumption::create([
            'material_id' => $material->id,
            'product_id' => $fixture['product']->id,
            'order_template_option_type_id' => $fixture['option_type']->id,
            'quantity' => 1,
        ]);

        $this->actingAs($customer)
            ->postJson('/api/customer-cart/items', [
                'product_id' => $fixture['product']->id,
                'order_template_id' => $fixture['template']->id,
                'selected_options' => [
                    (string) $fixture['option']->id => $fixture['option_type']->id,
                ],
                'quantity' => 1,
            ])
            ->assertCreated();

        if (Schema::hasColumn('customer_order_groups', 'payment_status')) {
            DB::statement('DROP INDEX IF EXISTS customer_order_groups_payment_status_index');

            Schema::table('customer_order_groups', function ($table) {
                $table->dropColumn('payment_status');
            });
        }

        $response = $this->actingAs($customer)
            ->postJson('/api/customer-cart/checkout', [
                'general_drive_link' => 'https://drive.google.com/drive/folders/schema-drift-case',
            ]);

        $response
            ->assertStatus(500)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'schema_mismatch')
            ->assertJsonPath('message', 'Checkout failed due to database schema mismatch.');
    }

    public function test_direct_single_order_accepts_blank_main_drive_link_when_optional(): void
    {
        $customer = User::factory()->create();
        $fixture = $this->createTemplateFixture('Optional Drive Link Product');

        $material = Material::create([
            'name' => 'Optional Drive Material '.uniqid(),
            'units' => 25,
        ]);

        MaterialConsumption::create([
            'material_id' => $material->id,
            'product_id' => $fixture['product']->id,
            'order_template_option_type_id' => $fixture['option_type']->id,
            'quantity' => 1,
        ]);

        $response = $this
            ->actingAs($customer)
            ->postJson('/api/customer-orders', [
                'product_id' => $fixture['product']->id,
                'order_template_id' => $fixture['template']->id,
                'selected_options' => [
                    (string) $fixture['option']->id => $fixture['option_type']->id,
                ],
                'quantity' => 1,
                'general_drive_link' => '   ',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('customer_order_groups', [
            'id' => $response->json('data.order_group_id'),
            'general_drive_link' => null,
        ]);
    }

    public function test_direct_single_order_rejects_invalid_main_drive_link_when_provided(): void
    {
        $customer = User::factory()->create();
        $fixture = $this->createTemplateFixture('Invalid Drive Link Product');

        $response = $this
            ->actingAs($customer)
            ->postJson('/api/customer-orders', [
                'product_id' => $fixture['product']->id,
                'order_template_id' => $fixture['template']->id,
                'selected_options' => [
                    (string) $fixture['option']->id => $fixture['option_type']->id,
                ],
                'quantity' => 1,
                'general_drive_link' => 'https://not-google-drive.example/order-folder',
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['general_drive_link']);
    }

    public function test_owner_edit_rejects_invalid_main_drive_link(): void
    {
        $owner = User::factory()->create(['user_type' => 'owner']);
        $customer = User::factory()->create();
        $fixture = $this->createTemplateFixture('Owner Invalid Drive Link Product');

        $group = CustomerOrderGroup::create([
            'user_id' => $customer->id,
            'status' => 'waiting',
            'general_drive_link' => 'https://drive.google.com/drive/folders/owner-valid-folder',
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

        $response = $this
            ->actingAs($owner)
            ->patchJson("/api/owner/orders/{$group->id}/details", [
                'general_drive_link' => 'https://example.com/owner-invalid-link',
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
            'general_drive_link' => 'https://drive.google.com/drive/folders/sample-owner-order',
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
            'general_drive_link' => 'https://drive.google.com/drive/folders/original-link',
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
                'general_drive_link' => 'https://drive.google.com/drive/folders/updated-link',
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
            ->assertJsonPath('data.general_drive_link', 'https://drive.google.com/drive/folders/updated-link')
            ->assertJsonPath('data.orders.0.quantity', 4)
            ->assertJsonPath('data.orders.0.rush_fee_id', $rushFee->id)
            ->assertJsonPath('data.orders.0.special_instructions', 'front.png,back.png')
            ->assertJsonPath('data.totals.subtotal_price', 480)
            ->assertJsonPath('data.totals.rush_fee_total', 48)
            ->assertJsonPath('data.totals.layout_fee_total', 0)
            ->assertJsonPath('data.totals.total_price', 528);

        $this->assertDatabaseHas('customer_order_groups', [
            'id' => $group->id,
            'general_drive_link' => 'https://drive.google.com/drive/folders/updated-link',
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
                'general_drive_link' => 'https://drive.google.com/drive/folders/should-not-save',
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
            ->assertJsonPath('shortages.0.low_stock_threshold', 5)
            ->assertJsonPath('shortages.0.safe_available', 0)
            ->assertJsonPath('shortages.0.max_allowed_quantity', 0)
            ->assertJsonPath('shortages.0.deficit', 3);

        $this->assertDatabaseHas('customer_orders', [
            'id' => $order->id,
            'quantity' => 1,
            'total_price' => 100.00,
        ]);

        $material->refresh();
        $this->assertSame(1, (int) $material->units);
    }

    public function test_customer_can_submit_payment_proof_and_owner_can_confirm_payment(): void
    {
        $customer = User::factory()->create();
        $owner = User::factory()->create(['user_type' => 'owner']);
        $fixture = $this->createTemplateFixture('Payment Flow Product');
        $records = $this->createGroupedOrderRecord(
            customer: $customer,
            fixture: $fixture,
            status: 'approved',
            paymentStatus: 'awaiting_payment',
        );

        $submitResponse = $this
            ->actingAs($customer)
            ->post("/api/customer-orders/{$records['group']->id}/payment-proof", [
                'payment_method' => 'gcash',
                'payment_reference_number' => '1234567890',
                'payment_proof' => $this->fakePngUpload('payment-proof.png'),
            ], [
                'Accept' => 'application/json',
            ]);

        $submitResponse
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.payment_status', 'waiting_payment_confirmation');

        $confirmResponse = $this
            ->actingAs($owner)
            ->patchJson("/api/owner/orders/{$records['group']->id}/payment-confirmation", [
                'payment_confirmation_note' => 'Confirmed against submitted proof.',
            ]);

        $confirmResponse
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.payment_status', 'payment_received')
            ->assertJsonPath('data.status', 'preparing');

        $this->assertDatabaseHas('customer_order_groups', [
            'id' => $records['group']->id,
            'payment_status' => 'payment_received',
            'status' => 'preparing',
        ]);

        $this->assertDatabaseHas('customer_orders', [
            'id' => $records['order']->id,
            'status' => 'preparing',
        ]);
    }

    public function test_customer_cannot_submit_payment_proof_before_approval(): void
    {
        $customer = User::factory()->create();
        $fixture = $this->createTemplateFixture('Premature Payment Product');
        $records = $this->createGroupedOrderRecord(
            customer: $customer,
            fixture: $fixture,
            status: 'waiting',
            paymentStatus: 'awaiting_payment',
        );

        $response = $this
            ->actingAs($customer)
            ->post("/api/customer-orders/{$records['group']->id}/payment-proof", [
                'payment_method' => 'gcash',
                'payment_reference_number' => '1234567890',
                'payment_proof' => $this->fakePngUpload('invalid-stage.png'),
            ], [
                'Accept' => 'application/json',
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'customer_order_payment_not_allowed');
    }

    public function test_customer_can_cancel_waiting_order_but_not_approved_order(): void
    {
        $customer = User::factory()->create();
        $fixture = $this->createTemplateFixture('Customer Cancel Product');

        $waitingRecords = $this->createGroupedOrderRecord(
            customer: $customer,
            fixture: $fixture,
            status: 'waiting',
            paymentStatus: 'awaiting_payment',
        );

        $cancelWaitingResponse = $this
            ->actingAs($customer)
            ->patchJson("/api/customer-orders/{$waitingRecords['group']->id}/cancel");

        $cancelWaitingResponse
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'cancelled')
            ->assertJsonPath('data.payment_status', 'payment_cancelled')
            ->assertJsonPath('data.cancellation_reason', 'customer_cancelled');

        $approvedRecords = $this->createGroupedOrderRecord(
            customer: $customer,
            fixture: $fixture,
            status: 'approved',
            paymentStatus: 'awaiting_payment',
        );

        $cancelApprovedResponse = $this
            ->actingAs($customer)
            ->patchJson("/api/customer-orders/{$approvedRecords['group']->id}/cancel");

        $cancelApprovedResponse
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'customer_order_cancel_not_allowed');
    }

    public function test_owner_cannot_move_approved_order_to_preparing_until_payment_is_confirmed(): void
    {
        $customer = User::factory()->create();
        $owner = User::factory()->create(['user_type' => 'owner']);
        $fixture = $this->createTemplateFixture('Preparing Guard Product');
        $records = $this->createGroupedOrderRecord(
            customer: $customer,
            fixture: $fixture,
            status: 'approved',
            paymentStatus: 'awaiting_payment',
        );

        $response = $this
            ->actingAs($owner)
            ->patchJson("/api/owner/orders/{$records['group']->id}/status", [
                'status' => 'preparing',
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'owner_payment_confirmation_required');
    }

    public function test_owner_cancelling_waiting_order_sets_decline_reason_and_payment_cancelled(): void
    {
        $customer = User::factory()->create();
        $owner = User::factory()->create(['user_type' => 'owner']);
        $fixture = $this->createTemplateFixture('Owner Decline Product');
        $records = $this->createGroupedOrderRecord(
            customer: $customer,
            fixture: $fixture,
            status: 'waiting',
            paymentStatus: 'awaiting_payment',
        );

        $response = $this
            ->actingAs($owner)
            ->patchJson("/api/owner/orders/{$records['group']->id}/status", [
                'status' => 'cancelled',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled')
            ->assertJsonPath('data.payment_status', 'payment_cancelled')
            ->assertJsonPath('data.cancellation_reason', 'owner_declined');
    }

    /**
     * @param array{product: Product, template: OrderTemplate, option: OrderTemplateOption, option_type: OrderTemplateOptionType} $fixture
     * @return array{group: CustomerOrderGroup, order: CustomerOrder}
     */
    private function createGroupedOrderRecord(User $customer, array $fixture, string $status, string $paymentStatus): array
    {
        $group = CustomerOrderGroup::create([
            'user_id' => $customer->id,
            'status' => $status,
            'payment_status' => $paymentStatus,
            'general_drive_link' => 'https://drive.google.com/drive/folders/payment-flow-fixture',
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
            'status' => $status,
        ]);

        return [
            'group' => $group,
            'order' => $order,
        ];
    }

    private function fakePngUpload(string $filename): UploadedFile
    {
        $tinyPng = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO6Nf9sAAAAASUVORK5CYII=',
            true,
        );

        if (! is_string($tinyPng)) {
            throw new \RuntimeException('Failed to build PNG test fixture payload.');
        }

        return UploadedFile::fake()->createWithContent($filename, $tinyPng);
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
