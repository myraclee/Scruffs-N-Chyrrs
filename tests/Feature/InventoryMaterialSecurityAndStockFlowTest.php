<?php

namespace Tests\Feature;

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
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryMaterialSecurityAndStockFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_read_materials_but_cannot_write_materials(): void
    {
        Material::create([
            'name' => 'Glossy Lamination',
            'units' => 12,
        ]);

        $this->getJson('/api/materials')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->postJson('/api/materials', [
            'name' => 'Sticker Paper',
            'units' => 20,
        ])->assertUnauthorized();

        $material = Material::firstOrFail();

        $this->putJson("/api/materials/{$material->id}", [
            'name' => 'Glossy Lamination Updated',
            'units' => 8,
        ])->assertUnauthorized();

        $this->deleteJson("/api/materials/{$material->id}")
            ->assertUnauthorized();
    }

    public function test_guest_can_filter_and_sort_materials_via_index_query_params(): void
    {
        Material::create([
            'name' => 'Card Stock '.uniqid(),
            'units' => 8,
            'max_units' => 10,
        ]);

        Material::create([
            'name' => 'Calling Card Sheets '.uniqid(),
            'units' => 6,
            'max_units' => 10,
        ]);

        Material::create([
            'name' => 'Sticker Paper '.uniqid(),
            'units' => 20,
            'max_units' => 20,
        ]);

        $response = $this->getJson('/api/materials?search=card&sort_by=units&sort_direction=desc');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.units', 8)
            ->assertJsonPath('data.1.units', 6);
    }

    public function test_guest_can_filter_materials_by_stock_band_via_index_query_params(): void
    {
        Material::create([
            'name' => 'High Film '.uniqid(),
            'units' => 9,
            'max_units' => 10,
        ]);

        Material::create([
            'name' => 'Medium Film '.uniqid(),
            'units' => 5,
            'max_units' => 10,
        ]);

        Material::create([
            'name' => 'Low Film '.uniqid(),
            'units' => 2,
            'max_units' => 10,
        ]);

        Material::create([
            'name' => 'Out Film '.uniqid(),
            'units' => 0,
            'max_units' => 10,
        ]);

        $lowBandResponse = $this->getJson('/api/materials?stock_band=low');
        $outBandResponse = $this->getJson('/api/materials?stock_band=out_of_stock');

        $lowBandResponse
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.stock_band', 'low');

        $outBandResponse
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.stock_band', 'out_of_stock');
    }

    public function test_material_index_rejects_invalid_filter_and_sort_query_params(): void
    {
        $this->getJson('/api/materials?stock_band=critical')
            ->assertStatus(422)
            ->assertJsonValidationErrors('stock_band');

        $this->getJson('/api/materials?sort_by=created_on')
            ->assertStatus(422)
            ->assertJsonValidationErrors('sort_by');

        $this->getJson('/api/materials?sort_direction=sideways')
            ->assertStatus(422)
            ->assertJsonValidationErrors('sort_direction');
    }

    public function test_non_owner_user_cannot_write_materials(): void
    {
        $customer = User::factory()->create([
            'user_type' => 'customer',
        ]);

        $material = Material::create([
            'name' => 'Card Stock',
            'units' => 6,
        ]);

        $this->actingAs($customer)
            ->postJson('/api/materials', [
                'name' => 'Premium Vinyl',
                'units' => 10,
            ])
            ->assertForbidden();

        $this->actingAs($customer)
            ->putJson("/api/materials/{$material->id}", [
                'name' => 'Card Stock Updated',
                'units' => 5,
            ])
            ->assertForbidden();

        $this->actingAs($customer)
            ->deleteJson("/api/materials/{$material->id}")
            ->assertForbidden();
    }

    public function test_owner_can_delete_materials(): void
    {
        $owner = User::factory()->create([
            'user_type' => 'owner',
        ]);

        $material = Material::create([
            'name' => 'Delete Me',
            'units' => 11,
        ]);

        $this->actingAs($owner)
            ->deleteJson("/api/materials/{$material->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('materials', [
            'id' => $material->id,
        ]);
    }

    public function test_material_low_stock_threshold_defaults_to_five(): void
    {
        $material = Material::create([
            'name' => 'Threshold Default '.uniqid(),
            'units' => 9,
        ]);

        $material->refresh();

        $this->assertSame(5, (int) $material->low_stock_threshold);
        $this->assertDatabaseHas('materials', [
            'id' => $material->id,
            'low_stock_threshold' => 5,
        ]);
    }

    public function test_owner_can_create_material_with_custom_low_stock_threshold(): void
    {
        $owner = User::factory()->create([
            'user_type' => 'owner',
        ]);

        $response = $this->actingAs($owner)
            ->postJson('/api/materials', [
                'name' => 'Threshold Custom '.uniqid(),
                'units' => 20,
                'low_stock_threshold' => 3,
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.low_stock_threshold', 3);
    }

    public function test_owner_can_create_material_with_custom_max_units_and_stock_band_metadata(): void
    {
        $owner = User::factory()->create([
            'user_type' => 'owner',
        ]);

        $response = $this->actingAs($owner)
            ->postJson('/api/materials', [
                'name' => 'Capacity Aware '.uniqid(),
                'units' => 14,
                'max_units' => 20,
                'low_stock_threshold' => 3,
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.max_units', 20)
            ->assertJsonPath('data.stock_band', 'medium');

        $this->assertDatabaseHas('materials', [
            'id' => (int) $response->json('data.id'),
            'max_units' => 20,
        ]);
    }

    public function test_owner_create_defaults_max_units_to_units_when_omitted(): void
    {
        $owner = User::factory()->create([
            'user_type' => 'owner',
        ]);

        $response = $this->actingAs($owner)
            ->postJson('/api/materials', [
                'name' => 'Implicit Capacity '.uniqid(),
                'units' => 11,
                'low_stock_threshold' => 2,
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.max_units', 11);
    }

    public function test_owner_cannot_create_material_when_max_units_is_below_units(): void
    {
        $owner = User::factory()->create([
            'user_type' => 'owner',
        ]);

        $this->actingAs($owner)
            ->postJson('/api/materials', [
                'name' => 'Invalid Capacity '.uniqid(),
                'units' => 12,
                'max_units' => 5,
                'low_stock_threshold' => 2,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('max_units');
    }

    public function test_owner_cannot_create_material_with_threshold_below_one(): void
    {
        $owner = User::factory()->create([
            'user_type' => 'owner',
        ]);

        $this->actingAs($owner)
            ->postJson('/api/materials', [
                'name' => 'Threshold Invalid '.uniqid(),
                'units' => 12,
                'low_stock_threshold' => 0,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('low_stock_threshold');
    }

    public function test_owner_can_create_material_with_fallback_and_option_specific_consumptions(): void
    {
        $owner = User::factory()->create([
            'user_type' => 'owner',
        ]);

        $product = Product::create([
            'name' => 'Consumption Product '.uniqid(),
            'description' => 'Consumption config product',
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
            'type_name' => 'Glossy',
            'is_available' => true,
            'position' => 1,
        ]);

        $response = $this->actingAs($owner)
            ->postJson('/api/materials', [
                'name' => 'Spec Material '.uniqid(),
                'units' => 25,
                'low_stock_threshold' => 4,
                'consumptions' => [
                    [
                        'product_id' => $product->id,
                        'order_template_option_type_id' => null,
                        'quantity' => 2,
                    ],
                    [
                        'product_id' => $product->id,
                        'order_template_option_type_id' => $optionType->id,
                        'quantity' => 3,
                    ],
                ],
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.consumptions.0.product_id', $product->id)
            ->assertJsonPath('data.products.0.pivot.quantity', 2);

        $materialId = (int) $response->json('data.id');

        $this->assertDatabaseHas('material_consumptions', [
            'material_id' => $materialId,
            'product_id' => $product->id,
            'order_template_option_type_id' => null,
            'quantity' => 2,
        ]);

        $this->assertDatabaseHas('material_consumptions', [
            'material_id' => $materialId,
            'product_id' => $product->id,
            'order_template_option_type_id' => $optionType->id,
            'quantity' => 3,
        ]);
    }

    public function test_checkout_deducts_stock_and_waiting_cancellation_restores_once(): void
    {
        $owner = User::factory()->create(['user_type' => 'owner']);
        $customer = User::factory()->create(['user_type' => 'customer']);
        $fixture = $this->createInventoryFixture(materialUnits: 50, pivotQuantity: 3);

        $this->actingAs($customer)
            ->postJson('/api/customer-cart/items', [
                'product_id' => $fixture['product']->id,
                'order_template_id' => $fixture['template']->id,
                'selected_options' => [
                    (string) $fixture['option']->id => $fixture['optionType']->id,
                ],
                'quantity' => 2,
            ])
            ->assertCreated()
            ->assertJsonPath('success', true);

        $checkoutResponse = $this->actingAs($customer)
            ->postJson('/api/customer-cart/checkout', [
                'general_drive_link' => 'https://drive.google.com/drive/folders/test-checkout',
            ]);

        $checkoutResponse
            ->assertOk()
            ->assertJsonPath('success', true);

        $groupId = $checkoutResponse->json('data.order_group_id');

        $group = CustomerOrderGroup::findOrFail($groupId);
        $this->assertNotNull($group->inventory_deducted_at);
        $this->assertNull($group->inventory_restored_at);
        $this->assertNotEmpty($group->inventory_material_requirements);

        $this->assertSame(
            6,
            (int) ($group->inventory_material_requirements[0]['required'] ?? 0)
        );

        $fixture['material']->refresh();
        $this->assertSame(44, (int) $fixture['material']->units);

        $this->actingAs($owner)
            ->patchJson("/api/owner/orders/{$groupId}/status", [
                'status' => 'cancelled',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'cancelled');

        $fixture['material']->refresh();
        $group->refresh();

        $this->assertSame(50, (int) $fixture['material']->units);
        $this->assertNotNull($group->inventory_restored_at);

        $this->actingAs($owner)
            ->patchJson("/api/owner/orders/{$groupId}/status", [
                'status' => 'cancelled',
            ])
            ->assertOk();

        $fixture['material']->refresh();
        $this->assertSame(50, (int) $fixture['material']->units);
    }

    public function test_checkout_blocks_when_material_stock_is_insufficient(): void
    {
        $customer = User::factory()->create(['user_type' => 'customer']);
        $fixture = $this->createInventoryFixture(materialUnits: 1, pivotQuantity: 2);

        $this->actingAs($customer)
            ->postJson('/api/customer-cart/items', [
                'product_id' => $fixture['product']->id,
                'order_template_id' => $fixture['template']->id,
                'selected_options' => [
                    (string) $fixture['option']->id => $fixture['optionType']->id,
                ],
                'quantity' => 1,
            ])
            ->assertCreated();

        $response = $this->actingAs($customer)
            ->postJson('/api/customer-cart/checkout', [
                'general_drive_link' => 'https://drive.google.com/drive/folders/test-shortage',
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('shortages.0.material_id', $fixture['material']->id)
            ->assertJsonPath('shortages.0.required', 2)
            ->assertJsonPath('shortages.0.available', 1)
            ->assertJsonPath('shortages.0.deficit', 1);

        $this->assertDatabaseCount('customer_order_groups', 0);

        $fixture['material']->refresh();
        $this->assertSame(1, (int) $fixture['material']->units);
    }

    public function test_cancellation_from_preparing_does_not_restore_stock(): void
    {
        $owner = User::factory()->create(['user_type' => 'owner']);
        $customer = User::factory()->create(['user_type' => 'customer']);
        $fixture = $this->createInventoryFixture(materialUnits: 30, pivotQuantity: 3);

        $this->actingAs($customer)
            ->postJson('/api/customer-cart/items', [
                'product_id' => $fixture['product']->id,
                'order_template_id' => $fixture['template']->id,
                'selected_options' => [
                    (string) $fixture['option']->id => $fixture['optionType']->id,
                ],
                'quantity' => 2,
            ])
            ->assertCreated();

        $checkoutResponse = $this->actingAs($customer)
            ->postJson('/api/customer-cart/checkout', [
                'general_drive_link' => 'https://drive.google.com/drive/folders/no-restore-case',
            ])
            ->assertOk();

        $groupId = $checkoutResponse->json('data.order_group_id');

        $this->actingAs($owner)
            ->patchJson("/api/owner/orders/{$groupId}/status", [
                'status' => 'approved',
            ])
            ->assertOk();

        CustomerOrderGroup::query()->whereKey($groupId)->update([
            'payment_status' => 'payment_received',
        ]);

        $this->actingAs($owner)
            ->patchJson("/api/owner/orders/{$groupId}/status", [
                'status' => 'preparing',
            ])
            ->assertOk();

        $this->actingAs($owner)
            ->patchJson("/api/owner/orders/{$groupId}/status", [
                'status' => 'cancelled',
            ])
            ->assertOk();

        $fixture['material']->refresh();
        $this->assertSame(24, (int) $fixture['material']->units);
    }

    public function test_direct_single_order_deducts_material_stock(): void
    {
        $customer = User::factory()->create(['user_type' => 'customer']);
        $fixture = $this->createInventoryFixture(materialUnits: 10, pivotQuantity: 4);

        $response = $this->actingAs($customer)
            ->postJson('/api/customer-orders', [
                'product_id' => $fixture['product']->id,
                'order_template_id' => $fixture['template']->id,
                'selected_options' => [
                    (string) $fixture['option']->id => $fixture['optionType']->id,
                ],
                'quantity' => 2,
                'general_drive_link' => 'https://drive.google.com/drive/folders/direct-order',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true);

        $fixture['material']->refresh();
        $this->assertSame(2, (int) $fixture['material']->units);

        $group = CustomerOrderGroup::findOrFail($response->json('data.order_group_id'));
        $this->assertNotNull($group->inventory_deducted_at);
        $this->assertSame(
            8,
            (int) ($group->inventory_material_requirements[0]['required'] ?? 0)
        );
    }

    public function test_checkout_prefers_selected_option_type_rules_over_fallback_rules(): void
    {
        $customer = User::factory()->create(['user_type' => 'customer']);
        $fixture = $this->createInventoryFixture(materialUnits: 30, pivotQuantity: 1);

        MaterialConsumption::create([
            'material_id' => $fixture['material']->id,
            'product_id' => $fixture['product']->id,
            'order_template_option_type_id' => $fixture['optionType']->id,
            'quantity' => 2,
        ]);

        $this->actingAs($customer)
            ->postJson('/api/customer-cart/items', [
                'product_id' => $fixture['product']->id,
                'order_template_id' => $fixture['template']->id,
                'selected_options' => [
                    (string) $fixture['option']->id => $fixture['optionType']->id,
                ],
                'quantity' => 2,
            ])
            ->assertCreated();

        $checkoutResponse = $this->actingAs($customer)
            ->postJson('/api/customer-cart/checkout', [
                'general_drive_link' => 'https://drive.google.com/drive/folders/sum-rules-case',
            ]);

        $checkoutResponse
            ->assertOk()
            ->assertJsonPath('success', true);

        $group = CustomerOrderGroup::findOrFail($checkoutResponse->json('data.order_group_id'));

        $this->assertSame(
            4,
            (int) ($group->inventory_material_requirements[0]['required'] ?? 0),
        );

        $breakdown = $group->inventory_material_requirements[0]['breakdown'] ?? [];
        $this->assertCount(1, $breakdown);
        $this->assertSame('selected_option_type', $breakdown[0]['source'] ?? null);

        $fixture['material']->refresh();
        $this->assertSame(26, (int) $fixture['material']->units);
    }

    public function test_checkout_applies_fallback_rules_and_blocks_when_fallback_material_is_insufficient(): void
    {
        $customer = User::factory()->create(['user_type' => 'customer']);
        $fixture = $this->createInventoryFixture(materialUnits: 10, pivotQuantity: 1);

        MaterialConsumption::create([
            'material_id' => $fixture['material']->id,
            'product_id' => $fixture['product']->id,
            'order_template_option_type_id' => $fixture['optionType']->id,
            'quantity' => 1,
        ]);

        $unrelatedFallbackMaterial = Material::create([
            'name' => 'Unrelated fallback '.uniqid(),
            'units' => 0,
        ]);

        MaterialConsumption::create([
            'material_id' => $unrelatedFallbackMaterial->id,
            'product_id' => $fixture['product']->id,
            'order_template_option_type_id' => null,
            'quantity' => 5,
        ]);

        $this->actingAs($customer)
            ->postJson('/api/customer-cart/items', [
                'product_id' => $fixture['product']->id,
                'order_template_id' => $fixture['template']->id,
                'selected_options' => [
                    (string) $fixture['option']->id => $fixture['optionType']->id,
                ],
                'quantity' => 1,
            ])
            ->assertCreated();

        $checkoutResponse = $this->actingAs($customer)
            ->postJson('/api/customer-cart/checkout', [
                'general_drive_link' => 'https://drive.google.com/drive/folders/selected-only-checkout',
            ]);

        $checkoutResponse
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('shortages.0.material_id', $unrelatedFallbackMaterial->id)
            ->assertJsonPath('shortages.0.required', 5)
            ->assertJsonPath('shortages.0.available', 0)
            ->assertJsonPath('shortages.0.deficit', 5);

        $fixture['material']->refresh();
        $unrelatedFallbackMaterial->refresh();

        $this->assertSame(10, (int) $fixture['material']->units);
        $this->assertSame(0, (int) $unrelatedFallbackMaterial->units);
    }

    public function test_checkout_deduplicates_same_material_and_uses_highest_quantity_across_selected_options(): void
    {
        $customer = User::factory()->create(['user_type' => 'customer']);

        $product = Product::create([
            'name' => 'Dedup Product '.uniqid(),
            'description' => 'Dedupe fixture',
            'cover_image_path' => '/images/fixture.png',
        ]);

        $material = Material::create([
            'name' => 'Dedup Material '.uniqid(),
            'units' => 40,
        ]);

        $template = OrderTemplate::create([
            'product_id' => $product->id,
        ]);

        $typeOption = OrderTemplateOption::create([
            'order_template_id' => $template->id,
            'label' => 'Type',
            'position' => 1,
        ]);

        $kisscutType = OrderTemplateOptionType::create([
            'order_template_option_id' => $typeOption->id,
            'type_name' => 'Kisscut',
            'is_available' => true,
            'position' => 1,
        ]);

        $laminationOption = OrderTemplateOption::create([
            'order_template_id' => $template->id,
            'label' => 'Lamination',
            'position' => 2,
        ]);

        $glossyType = OrderTemplateOptionType::create([
            'order_template_option_id' => $laminationOption->id,
            'type_name' => 'Glossy',
            'is_available' => true,
            'position' => 1,
        ]);

        MaterialConsumption::create([
            'material_id' => $material->id,
            'product_id' => $product->id,
            'order_template_option_type_id' => $kisscutType->id,
            'quantity' => 2,
        ]);

        MaterialConsumption::create([
            'material_id' => $material->id,
            'product_id' => $product->id,
            'order_template_option_type_id' => $glossyType->id,
            'quantity' => 5,
        ]);

        $combinationIds = [$kisscutType->id, $glossyType->id];
        sort($combinationIds);

        OrderTemplatePricing::create([
            'order_template_id' => $template->id,
            'combination_key' => implode(',', $combinationIds),
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

        $this->actingAs($customer)
            ->postJson('/api/customer-cart/items', [
                'product_id' => $product->id,
                'order_template_id' => $template->id,
                'selected_options' => [
                    (string) $typeOption->id => $kisscutType->id,
                    (string) $laminationOption->id => $glossyType->id,
                ],
                'quantity' => 3,
            ])
            ->assertCreated();

        $checkoutResponse = $this->actingAs($customer)
            ->postJson('/api/customer-cart/checkout', [
                'general_drive_link' => 'https://drive.google.com/drive/folders/dedupe-highest-checkout',
            ]);

        $checkoutResponse
            ->assertOk()
            ->assertJsonPath('success', true);

        $group = CustomerOrderGroup::findOrFail($checkoutResponse->json('data.order_group_id'));
        $requirements = $group->inventory_material_requirements ?? [];

        $this->assertSame(15, (int) ($requirements[0]['required'] ?? 0));
        $this->assertSame(
            'highest_quantity_wins',
            (string) ($requirements[0]['breakdown'][0]['dedupe_strategy'] ?? ''),
        );
        $this->assertSame(2, (int) ($requirements[0]['breakdown'][0]['material_match_count'] ?? 0));

        $material->refresh();
        $this->assertSame(25, (int) $material->units);
    }

    public function test_checkout_blocks_when_product_has_no_material_mapping_rows(): void
    {
        $customer = User::factory()->create(['user_type' => 'customer']);

        $product = Product::create([
            'name' => 'No Mapping Product '.uniqid(),
            'description' => 'No mapping fixture',
            'cover_image_path' => '/images/fixture.png',
        ]);

        $template = OrderTemplate::create([
            'product_id' => $product->id,
        ]);

        $option = OrderTemplateOption::create([
            'order_template_id' => $template->id,
            'label' => 'Type',
            'position' => 1,
        ]);

        $optionType = OrderTemplateOptionType::create([
            'order_template_option_id' => $option->id,
            'type_name' => 'Diecut',
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

        $this->actingAs($customer)
            ->postJson('/api/customer-cart/items', [
                'product_id' => $product->id,
                'order_template_id' => $template->id,
                'selected_options' => [
                    (string) $option->id => $optionType->id,
                ],
                'quantity' => 2,
            ])
            ->assertCreated();

        $response = $this->actingAs($customer)
            ->postJson('/api/customer-cart/checkout', [
                'general_drive_link' => 'https://drive.google.com/drive/folders/missing-mapping',
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('configuration_issues.0.product_id', $product->id)
            ->assertJsonPath('configuration_issues.0.issue', 'missing_product_option_mappings');

        $this->assertDatabaseCount('customer_order_groups', 0);
    }

    public function test_direct_order_is_blocked_when_product_template_has_no_options(): void
    {
        $customer = User::factory()->create(['user_type' => 'customer']);

        $product = Product::create([
            'name' => 'No Option Product '.uniqid(),
            'description' => 'Missing option configuration',
            'cover_image_path' => '/images/fixture.png',
        ]);

        $material = Material::create([
            'name' => 'No Option Material '.uniqid(),
            'units' => 20,
        ]);

        $template = OrderTemplate::create([
            'product_id' => $product->id,
        ]);

        OrderTemplatePricing::create([
            'order_template_id' => $template->id,
            'combination_key' => '99',
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

        MaterialConsumption::create([
            'material_id' => $material->id,
            'product_id' => $product->id,
            'order_template_option_type_id' => null,
            'quantity' => 1,
        ]);

        $response = $this->actingAs($customer)
            ->postJson('/api/customer-orders', [
                'product_id' => $product->id,
                'order_template_id' => $template->id,
                'selected_options' => ['dummy' => 99],
                'quantity' => 1,
                'general_drive_link' => 'https://drive.google.com/drive/folders/no-options-blocked',
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('configuration_issues.0.product_id', $product->id)
            ->assertJsonPath(
                'configuration_issues.0.issue',
                'missing_template_or_options',
            );

        $this->assertDatabaseCount('customer_order_groups', 0);

        $material->refresh();
        $this->assertSame(20, (int) $material->units);
    }

    /**
     * @return array{product: Product, material: Material, template: OrderTemplate, option: OrderTemplateOption, optionType: OrderTemplateOptionType}
     */
    private function createInventoryFixture(int $materialUnits, int $pivotQuantity): array
    {
        $product = Product::create([
            'name' => 'Inventory Fixture Product '.uniqid(),
            'description' => 'Fixture product description',
            'cover_image_path' => '/images/fixture.png',
        ]);

        $material = Material::create([
            'name' => 'Material '.uniqid(),
            'units' => $materialUnits,
        ]);

        MaterialConsumption::create([
            'material_id' => $material->id,
            'product_id' => $product->id,
            'order_template_option_type_id' => null,
            'quantity' => $pivotQuantity,
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
            'material' => $material,
            'template' => $template,
            'option' => $option,
            'optionType' => $optionType,
        ];
    }
}
