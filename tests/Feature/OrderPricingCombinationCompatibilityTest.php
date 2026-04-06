<?php

namespace Tests\Feature;

use App\Models\Material;
use App\Models\MaterialConsumption;
use App\Models\OrderTemplate;
use App\Models\OrderTemplateOption;
use App\Models\OrderTemplateOptionType;
use App\Models\OrderTemplatePricing;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderPricingCombinationCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_flow_accepts_label_based_combination_key_format(): void
    {
        $customer = User::factory()->create();
        $fixture = $this->createTwoOptionTemplateFixture('Label Key Cart Product');

        OrderTemplatePricing::create([
            'order_template_id' => $fixture['template']->id,
            'combination_key' => 'Matte | 2x2 inches',
            'price' => 50,
        ]);

        $response = $this
            ->actingAs($customer)
            ->postJson('/api/customer-cart/items', [
                'product_id' => $fixture['product']->id,
                'order_template_id' => $fixture['template']->id,
                'selected_options' => [
                    (string) $fixture['finish_option']->id => $fixture['finish_matte']->id,
                    (string) $fixture['size_option']->id => $fixture['size_2x2']->id,
                ],
                'quantity' => 2,
                'special_instructions' => null,
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.items.0.base_price', 100)
            ->assertJsonPath('data.items.0.total_price', 100);
    }

    public function test_direct_order_flow_accepts_label_based_combination_key_format(): void
    {
        $customer = User::factory()->create();
        $fixture = $this->createTwoOptionTemplateFixture('Label Key Direct Product');

        OrderTemplatePricing::create([
            'order_template_id' => $fixture['template']->id,
            'combination_key' => 'Matte | 2x2 inches',
            'price' => 70,
        ]);

        $response = $this
            ->actingAs($customer)
            ->postJson('/api/customer-orders', [
                'product_id' => $fixture['product']->id,
                'order_template_id' => $fixture['template']->id,
                'selected_options' => [
                    (string) $fixture['finish_option']->id => $fixture['finish_matte']->id,
                    (string) $fixture['size_option']->id => $fixture['size_2x2']->id,
                ],
                'quantity' => 1,
                'general_drive_link' => 'https://drive.google.com/compat-order',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_price', 70);
    }

    public function test_missing_combination_returns_actionable_not_configured_message(): void
    {
        $customer = User::factory()->create();
        $fixture = $this->createTwoOptionTemplateFixture('Missing Combination Product');

        OrderTemplatePricing::create([
            'order_template_id' => $fixture['template']->id,
            'combination_key' => 'Glossy | 3x3 inches',
            'price' => 90,
        ]);

        $response = $this
            ->actingAs($customer)
            ->postJson('/api/customer-cart/items', [
                'product_id' => $fixture['product']->id,
                'order_template_id' => $fixture['template']->id,
                'selected_options' => [
                    (string) $fixture['finish_option']->id => $fixture['finish_matte']->id,
                    (string) $fixture['size_option']->id => $fixture['size_2x2']->id,
                ],
                'quantity' => 1,
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath(
                'message',
                'Selected option combination is not configured for pricing yet. Please choose another combination or contact support.',
            );
    }

    public function test_order_template_store_normalizes_label_pricing_key_to_canonical_numeric_key(): void
    {
        $product = Product::create([
            'name' => 'Template Save Normalization Product',
            'description' => 'Fixture product',
            'cover_image_path' => '/images/fixture.png',
        ]);

        $response = $this->postJson('/api/order-templates', [
            'product_id' => $product->id,
            'options' => [
                [
                    'label' => 'Finish',
                    'position' => 1,
                    'option_types' => [
                        ['type_name' => 'Matte', 'is_available' => true, 'position' => 1],
                        ['type_name' => 'Glossy', 'is_available' => true, 'position' => 2],
                    ],
                ],
                [
                    'label' => 'Size',
                    'position' => 2,
                    'option_types' => [
                        ['type_name' => '2x2 inches', 'is_available' => true, 'position' => 1],
                        ['type_name' => '3x3 inches', 'is_available' => true, 'position' => 2],
                    ],
                ],
            ],
            'pricings' => [
                [
                    'combination_key' => 'Matte | 2x2 inches',
                    'price' => 55,
                ],
            ],
            'discounts' => [],
            'min_order' => 1,
            'layout_fee' => 0,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true);

        $templateId = (int) $response->json('data.id');

        $template = OrderTemplate::with(['options.optionTypes', 'pricings'])
            ->findOrFail($templateId);

        $finishOption = $template->options->firstWhere('label', 'Finish');
        $sizeOption = $template->options->firstWhere('label', 'Size');

        $matteType = $finishOption?->optionTypes->firstWhere('type_name', 'Matte');
        $sizeType = $sizeOption?->optionTypes->firstWhere('type_name', '2x2 inches');

        $this->assertNotNull($matteType);
        $this->assertNotNull($sizeType);

        $typeIds = [(int) $matteType->id, (int) $sizeType->id];
        sort($typeIds);
        $expectedKey = implode(',', $typeIds);

        $this->assertDatabaseHas('order_template_pricings', [
            'order_template_id' => $templateId,
            'combination_key' => $expectedKey,
            'price' => 55.00,
        ]);

        $this->assertDatabaseMissing('order_template_pricings', [
            'order_template_id' => $templateId,
            'combination_key' => 'Matte | 2x2 inches',
        ]);
    }

    /**
     * @return array{
     *     product: Product,
     *     template: OrderTemplate,
     *     finish_option: OrderTemplateOption,
     *     size_option: OrderTemplateOption,
     *     finish_matte: OrderTemplateOptionType,
     *     finish_glossy: OrderTemplateOptionType,
     *     size_2x2: OrderTemplateOptionType,
     *     size_3x3: OrderTemplateOptionType
     * }
     */
    private function createTwoOptionTemplateFixture(string $name): array
    {
        $product = Product::create([
            'name' => $name,
            'description' => 'Fixture product description',
            'cover_image_path' => '/images/fixture.png',
        ]);

        $template = OrderTemplate::create([
            'product_id' => $product->id,
        ]);

        $finishOption = OrderTemplateOption::create([
            'order_template_id' => $template->id,
            'label' => 'Finish',
            'position' => 1,
        ]);

        $sizeOption = OrderTemplateOption::create([
            'order_template_id' => $template->id,
            'label' => 'Size',
            'position' => 2,
        ]);

        $finishMatte = OrderTemplateOptionType::create([
            'order_template_option_id' => $finishOption->id,
            'type_name' => 'Matte',
            'is_available' => true,
            'position' => 1,
        ]);

        $finishGlossy = OrderTemplateOptionType::create([
            'order_template_option_id' => $finishOption->id,
            'type_name' => 'Glossy',
            'is_available' => true,
            'position' => 2,
        ]);

        $size2x2 = OrderTemplateOptionType::create([
            'order_template_option_id' => $sizeOption->id,
            'type_name' => '2x2 inches',
            'is_available' => true,
            'position' => 1,
        ]);

        $size3x3 = OrderTemplateOptionType::create([
            'order_template_option_id' => $sizeOption->id,
            'type_name' => '3x3 inches',
            'is_available' => true,
            'position' => 2,
        ]);

        $material = Material::create([
            'name' => $name . ' Material',
            'units' => 1000,
            'low_stock_threshold' => 5,
            'description' => 'Fixture material for strict inventory mapping checks',
        ]);

        MaterialConsumption::create([
            'material_id' => $material->id,
            'product_id' => $product->id,
            'order_template_option_type_id' => null,
            'quantity' => 1,
        ]);

        return [
            'product' => $product,
            'template' => $template,
            'finish_option' => $finishOption,
            'size_option' => $sizeOption,
            'finish_matte' => $finishMatte,
            'finish_glossy' => $finishGlossy,
            'size_2x2' => $size2x2,
            'size_3x3' => $size3x3,
        ];
    }
}
