<?php

namespace Tests\Feature;

use App\Models\CustomerOrderGroup;
use App\Models\Material;
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
                'general_drive_link' => 'https://drive.google.com/test-checkout',
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
                'general_drive_link' => 'https://drive.google.com/test-shortage',
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
                'general_drive_link' => 'https://drive.google.com/no-restore-case',
            ])
            ->assertOk();

        $groupId = $checkoutResponse->json('data.order_group_id');

        $this->actingAs($owner)
            ->patchJson("/api/owner/orders/{$groupId}/status", [
                'status' => 'approved',
            ])
            ->assertOk();

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
                'general_drive_link' => 'https://drive.google.com/direct-order',
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

        $product->materials()->attach($material->id, [
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
