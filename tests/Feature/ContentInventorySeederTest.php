<?php

namespace Tests\Feature;

use App\Models\Material;
use App\Models\MaterialConsumption;
use App\Models\OrderTemplatePricing;
use App\Models\Product;
use App\Models\RushFee;
use Database\Seeders\ContentInventorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentInventorySeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_content_inventory_seeder_builds_expected_catalog_contract(): void
    {
        $this->seed(ContentInventorySeeder::class);

        $this->assertDatabaseCount('materials', 21);
        $this->assertDatabaseCount('products', 10);
        $this->assertDatabaseCount('order_templates', 10);
        $this->assertDatabaseCount('rush_fees', 4);
        $this->assertDatabaseCount('rush_fee_timeframes', 20);
        $this->assertGreaterThan(0, MaterialConsumption::query()->count());

        $buttonPins = Product::query()
            ->where('name', 'Button Pins')
            ->with('orderTemplate.options.optionTypes')
            ->first();

        $this->assertNotNull($buttonPins);
        $this->assertNotNull($buttonPins?->orderTemplate);

        $optionLabels = $buttonPins?->orderTemplate?->options->pluck('label')->all() ?? [];
        $this->assertEqualsCanonicalizing(['Size', 'Lamination'], $optionLabels);

        $sizeOption = $buttonPins?->orderTemplate?->options->firstWhere('label', 'Size');
        $this->assertNotNull($sizeOption);
        $this->assertCount(4, $sizeOption?->optionTypes ?? []);

        $photoPaper = Product::query()
            ->where('name', 'Photo-Paper Prints')
            ->with('orderTemplate.options.optionTypes')
            ->first();

        $this->assertNotNull($photoPaper);

        $cutStyleOption = $photoPaper?->orderTemplate?->options->firstWhere('label', 'Cut Style');
        $this->assertNotNull($cutStyleOption);
        $this->assertEqualsCanonicalizing(
            ['Standard Cut', 'Custom Cut'],
            $cutStyleOption?->optionTypes->pluck('type_name')->all() ?? []
        );

        $posterTemplate = Product::query()
            ->where('name', 'Posters - 3R')
            ->with('orderTemplate.discounts')
            ->first()?->orderTemplate;

        $this->assertNotNull($posterTemplate);

        $posterDiscount = $posterTemplate?->discounts->firstWhere('min_quantity', 10);
        $this->assertNotNull($posterDiscount);
        $this->assertSame(-20.0, (float) $posterDiscount?->price_reduction);

        $openEndedRushFee = RushFee::query()
            ->where('label', 'Orders P5001 and Above')
            ->with(['timeframes' => fn ($query) => $query->orderBy('sort_order')])
            ->first();

        $this->assertNotNull($openEndedRushFee);
        $this->assertNull($openEndedRushFee?->max_price);

        $lastTimeframe = $openEndedRushFee?->timeframes->last();
        $this->assertNotNull($lastTimeframe);
        $this->assertSame(0.0, (float) $lastTimeframe?->percentage);

        $samplePricing = OrderTemplatePricing::query()->first();
        $this->assertNotNull($samplePricing);
        $this->assertMatchesRegularExpression('/^\d+(,\d+)*$/', (string) $samplePricing?->combination_key);
    }

    public function test_content_inventory_seeder_is_idempotent_for_primary_entities(): void
    {
        $this->seed(ContentInventorySeeder::class);

        $firstCounts = [
            'materials' => Material::query()->count(),
            'products' => Product::query()->count(),
            'templates' => \App\Models\OrderTemplate::query()->count(),
            'options' => \App\Models\OrderTemplateOption::query()->count(),
            'option_types' => \App\Models\OrderTemplateOptionType::query()->count(),
            'pricings' => \App\Models\OrderTemplatePricing::query()->count(),
            'discounts' => \App\Models\OrderTemplateDiscount::query()->count(),
            'min_orders' => \App\Models\OrderTemplateMinOrder::query()->count(),
            'layout_fees' => \App\Models\OrderTemplateLayoutFee::query()->count(),
            'consumptions' => MaterialConsumption::query()->count(),
            'rush_fees' => RushFee::query()->count(),
            'rush_timeframes' => \App\Models\RushFeeTimeframe::query()->count(),
        ];

        $this->seed(ContentInventorySeeder::class);

        $secondCounts = [
            'materials' => Material::query()->count(),
            'products' => Product::query()->count(),
            'templates' => \App\Models\OrderTemplate::query()->count(),
            'options' => \App\Models\OrderTemplateOption::query()->count(),
            'option_types' => \App\Models\OrderTemplateOptionType::query()->count(),
            'pricings' => \App\Models\OrderTemplatePricing::query()->count(),
            'discounts' => \App\Models\OrderTemplateDiscount::query()->count(),
            'min_orders' => \App\Models\OrderTemplateMinOrder::query()->count(),
            'layout_fees' => \App\Models\OrderTemplateLayoutFee::query()->count(),
            'consumptions' => MaterialConsumption::query()->count(),
            'rush_fees' => RushFee::query()->count(),
            'rush_timeframes' => \App\Models\RushFeeTimeframe::query()->count(),
        ];

        $this->assertSame($firstCounts, $secondCounts);
    }
}
