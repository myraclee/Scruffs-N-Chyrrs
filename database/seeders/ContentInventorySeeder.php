<?php

namespace Database\Seeders;

use App\Models\Material;
use App\Models\MaterialConsumption;
use App\Models\OrderTemplate;
use App\Models\OrderTemplateDiscount;
use App\Models\OrderTemplateLayoutFee;
use App\Models\OrderTemplateMinOrder;
use App\Models\OrderTemplateOption;
use App\Models\OrderTemplateOptionType;
use App\Models\OrderTemplatePricing;
use App\Models\Product;
use App\Models\RushFee;
use App\Models\RushFeeTimeframe;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class ContentInventorySeeder extends Seeder
{
    private const LAMINATION_TYPES = [
        'Glossy',
        'Matte',
        'Glitter',
        'Holographic Rainbow',
        'Holographic Broken Glass',
    ];

    /**
     * @var array<string, string>
     */
    private const LAMINATION_MATERIAL_MAP = [
        'Glossy' => 'Glossy Lamination Film',
        'Matte' => 'Matte Lamination Film',
        'Glitter' => 'Glitter Lamination Film',
        'Holographic Rainbow' => 'Holographic Rainbow Lamination Film',
        'Holographic Broken Glass' => 'Holographic Broken Glass Lamination Film',
    ];

    public function run(): void
    {
        $this->command?->info('Seeding materials, products, order templates, and rush fees...');

        $materials = $this->seedMaterials();

        $consumptionBlueprints = [];

        foreach ($this->buildProductBlueprints() as $blueprint) {
            [$product, $optionTypeLookup] = $this->seedProductTemplate($blueprint);

            $consumptionBlueprints[] = [
                'product_id' => (int) $product->id,
                'option_type_lookup' => $optionTypeLookup,
                'consumptions' => $blueprint['consumptions'],
            ];
        }

        $this->seedMaterialConsumptions($materials, $consumptionBlueprints);
        $this->seedRushFees();

        $this->command?->info('Content inventory seeding completed.');
    }

    /**
     * @return array<string, Material>
     */
    private function seedMaterials(): array
    {
        $definitions = [
            ['name' => 'Vinyl Waterproof Sheet', 'units' => 6000, 'low_stock_threshold' => 800, 'description' => 'Primary sheet stock for vinyl sticker products.'],
            ['name' => 'CMYK Ink', 'units' => 12000, 'low_stock_threshold' => 1500, 'description' => 'General print ink consumption material.'],
            ['name' => 'Light-Pierce Blade Wear', 'units' => 3000, 'low_stock_threshold' => 300, 'description' => 'Cutting workload units for light-pierce operations.'],
            ['name' => 'Deep-Pierce Blade Wear', 'units' => 3000, 'low_stock_threshold' => 300, 'description' => 'Cutting workload units for deep-pierce operations.'],
            ['name' => 'Glossy Lamination Film', 'units' => 5000, 'low_stock_threshold' => 700, 'description' => 'Glossy lamination overlay material.'],
            ['name' => 'Matte Lamination Film', 'units' => 5000, 'low_stock_threshold' => 700, 'description' => 'Matte lamination overlay material.'],
            ['name' => 'Glitter Lamination Film', 'units' => 4500, 'low_stock_threshold' => 600, 'description' => 'Glitter lamination overlay material.'],
            ['name' => 'Holographic Rainbow Lamination Film', 'units' => 4200, 'low_stock_threshold' => 550, 'description' => 'Holographic rainbow lamination overlay material.'],
            ['name' => 'Holographic Broken Glass Lamination Film', 'units' => 4200, 'low_stock_threshold' => 550, 'description' => 'Holographic broken glass lamination overlay material.'],
            ['name' => 'Plastic Backing Pins', 'units' => 8000, 'low_stock_threshold' => 900, 'description' => 'Plastic pin backings for button pin products.'],
            ['name' => 'Button Pin Print Insert', 'units' => 8000, 'low_stock_threshold' => 900, 'description' => 'Printable insert circles used in button pins.'],
            ['name' => 'Button Pin Shell 32mm', 'units' => 3500, 'low_stock_threshold' => 400, 'description' => '1.25in / 32mm metal shell stock.'],
            ['name' => 'Button Pin Shell 37mm', 'units' => 3500, 'low_stock_threshold' => 400, 'description' => '1.45in / 37mm metal shell stock.'],
            ['name' => 'Button Pin Shell 44mm', 'units' => 3500, 'low_stock_threshold' => 400, 'description' => '1.75in / 44mm metal shell stock.'],
            ['name' => 'Button Pin Shell 58mm', 'units' => 3500, 'low_stock_threshold' => 400, 'description' => '2.25in / 58mm metal shell stock.'],
            ['name' => 'Photocard Paper 300gsm', 'units' => 5000, 'low_stock_threshold' => 650, 'description' => '300gsm paper stock for photocards.'],
            ['name' => 'Business Card Matte Paper 250gsm', 'units' => 5000, 'low_stock_threshold' => 650, 'description' => '250gsm matte paper stock for business cards.'],
            ['name' => 'RC Satin Poster Paper', 'units' => 5000, 'low_stock_threshold' => 650, 'description' => 'Resin-coated satin paper stock for posters.'],
            ['name' => 'Photo Paper 300gsm', 'units' => 5500, 'low_stock_threshold' => 700, 'description' => '300gsm photo paper stock for photo-paper products.'],
            ['name' => 'Standard Trim Wear', 'units' => 3200, 'low_stock_threshold' => 400, 'description' => 'General trim wear units for standard cuts.'],
            ['name' => 'Custom Cut Blade Wear', 'units' => 3200, 'low_stock_threshold' => 400, 'description' => 'Additional blade wear units for custom-cut processing.'],
        ];

        $materials = [];

        foreach ($definitions as $definition) {
            $material = Material::query()->updateOrCreate(
                ['name' => $definition['name']],
                [
                    'units' => $definition['units'],
                    'low_stock_threshold' => $definition['low_stock_threshold'],
                    'description' => $definition['description'],
                ]
            );

            $materials[$definition['name']] = $material;
        }

        return $materials;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildProductBlueprints(): array
    {
        $laminationsWithNone = array_merge(['No Lamination'], self::LAMINATION_TYPES);

        $buttonPinMatrix = [
            '1.25in (32mm)' => [
                'Glossy' => 10.00,
                'Matte' => 11.00,
                'Glitter' => 11.00,
                'Holographic Rainbow' => 13.00,
                'Holographic Broken Glass' => 13.00,
            ],
            '1.45in (37mm)' => [
                'Glossy' => 12.00,
                'Matte' => 13.00,
                'Glitter' => 13.00,
                'Holographic Rainbow' => 17.00,
                'Holographic Broken Glass' => 17.00,
            ],
            '1.75in (44mm)' => [
                'Glossy' => 14.00,
                'Matte' => 16.00,
                'Glitter' => 16.00,
                'Holographic Rainbow' => 18.00,
                'Holographic Broken Glass' => 18.00,
            ],
            '2.25in (58mm)' => [
                'Glossy' => 16.00,
                'Matte' => 18.00,
                'Glitter' => 18.00,
                'Holographic Rainbow' => 20.00,
                'Holographic Broken Glass' => 20.00,
            ],
        ];

        $photocardMatrix = [
            'No Lamination' => ['Single Side' => 45.00, 'Back to Back' => 65.00],
            'Glossy' => ['Single Side' => 50.00, 'Back to Back' => 75.00],
            'Matte' => ['Single Side' => 60.00, 'Back to Back' => 75.00],
            'Glitter' => ['Single Side' => 60.00, 'Back to Back' => 85.00],
            'Holographic Rainbow' => ['Single Side' => 70.00, 'Back to Back' => 90.00],
            'Holographic Broken Glass' => ['Single Side' => 70.00, 'Back to Back' => 90.00],
        ];

        $businessCardMatrix = [
            'No Lamination' => ['Single Side' => 5.00, 'Back to Back' => 9.00],
            'Glossy' => ['Single Side' => 6.00, 'Back to Back' => 11.00],
            'Matte' => ['Single Side' => 6.00, 'Back to Back' => 11.00],
            'Glitter' => ['Single Side' => 7.00, 'Back to Back' => 13.00],
            'Holographic Rainbow' => ['Single Side' => 8.00, 'Back to Back' => 15.00],
            'Holographic Broken Glass' => ['Single Side' => 8.00, 'Back to Back' => 15.00],
        ];

        $photoPaperMatrix = [
            'No Lamination' => ['Single Side' => 40.00, 'Back to Back' => 45.00],
            'Glossy' => ['Single Side' => 45.00, 'Back to Back' => 55.00],
            'Matte' => ['Single Side' => 55.00, 'Back to Back' => 65.00],
            'Glitter' => ['Single Side' => 55.00, 'Back to Back' => 65.00],
            'Holographic Rainbow' => ['Single Side' => 65.00, 'Back to Back' => 75.00],
            'Holographic Broken Glass' => ['Single Side' => 65.00, 'Back to Back' => 75.00],
        ];

        $photoPaperRows = [];
        foreach ($photoPaperMatrix as $lamination => $sidePrices) {
            foreach ($sidePrices as $printSide => $price) {
                foreach (['Standard Cut' => 0.00, 'Custom Cut' => 10.00] as $cutStyle => $additionalPrice) {
                    $photoPaperRows[] = [
                        'selectors' => [
                            'Lamination' => $lamination,
                            'Print Side' => $printSide,
                            'Cut Style' => $cutStyle,
                        ],
                        'price' => $price + $additionalPrice,
                    ];
                }
            }
        }

        return [
            [
                'name' => 'Kiss-Cut Stickers',
                'description' => 'A4 sheet | Vinyl & Waterproof | Light-pierce process.',
                'cover_image_path' => 'images/products/kiss-cut-stickers.jpg',
                'min_quantity' => 1,
                'layout_fee' => 0.00,
                'options' => [
                    ['label' => 'Lamination', 'types' => self::LAMINATION_TYPES],
                ],
                'pricing_rows' => $this->buildSingleOptionPricingRows('Lamination', [
                    'Glossy' => 50.00,
                    'Matte' => 55.00,
                    'Glitter' => 60.00,
                    'Holographic Rainbow' => 65.00,
                    'Holographic Broken Glass' => 70.00,
                ]),
                'discount_rows' => [
                    ['min_quantity' => 8, 'price_reduction' => 5.00],
                ],
                'consumptions' => $this->buildStickerConsumptions('Light-Pierce Blade Wear'),
            ],
            [
                'name' => 'Die-Cut Stickers',
                'description' => 'A4 sheet | Vinyl & Waterproof | Deep-pierce process.',
                'cover_image_path' => 'images/products/die-cut-stickers.jpg',
                'min_quantity' => 1,
                'layout_fee' => 0.00,
                'options' => [
                    ['label' => 'Lamination', 'types' => self::LAMINATION_TYPES],
                ],
                'pricing_rows' => $this->buildSingleOptionPricingRows('Lamination', [
                    'Glossy' => 45.00,
                    'Matte' => 50.00,
                    'Glitter' => 55.00,
                    'Holographic Rainbow' => 60.00,
                    'Holographic Broken Glass' => 65.00,
                ]),
                'discount_rows' => [
                    ['min_quantity' => 8, 'price_reduction' => 5.00],
                ],
                'consumptions' => $this->buildStickerConsumptions('Deep-Pierce Blade Wear'),
            ],
            [
                'name' => 'Button Pins',
                'description' => 'Plastic backing button pins with size variants (32mm to 58mm).',
                'cover_image_path' => 'images/products/button-pins.jpg',
                'min_quantity' => 10,
                'layout_fee' => 0.00,
                'options' => [
                    ['label' => 'Size', 'types' => array_keys($buttonPinMatrix)],
                    ['label' => 'Lamination', 'types' => self::LAMINATION_TYPES],
                ],
                'pricing_rows' => $this->buildDoubleOptionPricingRows('Size', 'Lamination', $buttonPinMatrix),
                'discount_rows' => [
                    ['min_quantity' => 10, 'price_reduction' => 3.00],
                ],
                'consumptions' => array_merge(
                    [
                        ['material' => 'Plastic Backing Pins', 'quantity' => 1],
                        ['material' => 'Button Pin Print Insert', 'quantity' => 1],
                        ['material' => 'CMYK Ink', 'quantity' => 1],
                    ],
                    $this->buildSizeSpecificConsumptions([
                        '1.25in (32mm)' => 'Button Pin Shell 32mm',
                        '1.45in (37mm)' => 'Button Pin Shell 37mm',
                        '1.75in (44mm)' => 'Button Pin Shell 44mm',
                        '2.25in (58mm)' => 'Button Pin Shell 58mm',
                    ]),
                    $this->buildLaminationSpecificConsumptions('Lamination')
                ),
            ],
            [
                'name' => 'Photocards',
                'description' => '8.5 x 5.5cm photocards on 300gsm stock.',
                'cover_image_path' => 'images/products/photocards.jpg',
                'min_quantity' => 1,
                'layout_fee' => 0.00,
                'options' => [
                    ['label' => 'Lamination', 'types' => $laminationsWithNone],
                    ['label' => 'Print Side', 'types' => ['Single Side', 'Back to Back']],
                ],
                'pricing_rows' => $this->buildDoubleOptionPricingRows('Lamination', 'Print Side', $photocardMatrix),
                'discount_rows' => [],
                'consumptions' => array_merge(
                    [
                        ['material' => 'Photocard Paper 300gsm', 'quantity' => 1],
                        ['material' => 'CMYK Ink', 'quantity' => 1],
                        ['material' => 'CMYK Ink', 'quantity' => 2, 'selector' => ['Print Side' => 'Back to Back']],
                    ],
                    $this->buildLaminationSpecificConsumptions('Lamination')
                ),
            ],
            [
                'name' => 'Business Cards',
                'description' => '3.25 x 2in cards on 250gsm matte paper.',
                'cover_image_path' => 'images/products/business-cards.jpg',
                'min_quantity' => 50,
                'layout_fee' => 0.00,
                'options' => [
                    ['label' => 'Lamination', 'types' => $laminationsWithNone],
                    ['label' => 'Print Side', 'types' => ['Single Side', 'Back to Back']],
                ],
                'pricing_rows' => $this->buildDoubleOptionPricingRows('Lamination', 'Print Side', $businessCardMatrix),
                'discount_rows' => [],
                'consumptions' => array_merge(
                    [
                        ['material' => 'Business Card Matte Paper 250gsm', 'quantity' => 1],
                        ['material' => 'CMYK Ink', 'quantity' => 1],
                        ['material' => 'CMYK Ink', 'quantity' => 2, 'selector' => ['Print Side' => 'Back to Back']],
                    ],
                    $this->buildLaminationSpecificConsumptions('Lamination')
                ),
            ],
            [
                'name' => 'Posters - 3R',
                'description' => 'RC Satin poster, 3R size, per-piece pricing.',
                'cover_image_path' => 'images/products/posters-3r.jpg',
                'min_quantity' => 1,
                'layout_fee' => 0.00,
                'options' => [
                    ['label' => 'Paper Type', 'types' => ['RC Satin']],
                ],
                'pricing_rows' => [
                    ['selectors' => ['Paper Type' => 'RC Satin'], 'price' => 45.00],
                ],
                'discount_rows' => [
                    ['min_quantity' => 10, 'price_reduction' => -20.00],
                ],
                'consumptions' => [
                    ['material' => 'RC Satin Poster Paper', 'quantity' => 1],
                    ['material' => 'CMYK Ink', 'quantity' => 1],
                    ['material' => 'Standard Trim Wear', 'quantity' => 1],
                ],
            ],
            [
                'name' => 'Posters - 4R',
                'description' => 'RC Satin poster, 4R size, per-piece pricing.',
                'cover_image_path' => 'images/products/posters-4r.jpg',
                'min_quantity' => 1,
                'layout_fee' => 0.00,
                'options' => [
                    ['label' => 'Paper Type', 'types' => ['RC Satin']],
                ],
                'pricing_rows' => [
                    ['selectors' => ['Paper Type' => 'RC Satin'], 'price' => 50.00],
                ],
                'discount_rows' => [
                    ['min_quantity' => 10, 'price_reduction' => -25.00],
                ],
                'consumptions' => [
                    ['material' => 'RC Satin Poster Paper', 'quantity' => 1],
                    ['material' => 'CMYK Ink', 'quantity' => 1],
                    ['material' => 'Standard Trim Wear', 'quantity' => 1],
                ],
            ],
            [
                'name' => 'Posters - 5R',
                'description' => 'RC Satin poster, 5R size, per-piece pricing.',
                'cover_image_path' => 'images/products/posters-5r.jpg',
                'min_quantity' => 1,
                'layout_fee' => 0.00,
                'options' => [
                    ['label' => 'Paper Type', 'types' => ['RC Satin']],
                ],
                'pricing_rows' => [
                    ['selectors' => ['Paper Type' => 'RC Satin'], 'price' => 60.00],
                ],
                'discount_rows' => [
                    ['min_quantity' => 10, 'price_reduction' => -15.00],
                ],
                'consumptions' => [
                    ['material' => 'RC Satin Poster Paper', 'quantity' => 1],
                    ['material' => 'CMYK Ink', 'quantity' => 1],
                    ['material' => 'Standard Trim Wear', 'quantity' => 1],
                ],
            ],
            [
                'name' => 'Posters - A4',
                'description' => 'RC Satin poster, A4 size, per-piece pricing.',
                'cover_image_path' => 'images/products/posters-a4.jpg',
                'min_quantity' => 1,
                'layout_fee' => 0.00,
                'options' => [
                    ['label' => 'Paper Type', 'types' => ['RC Satin']],
                ],
                'pricing_rows' => [
                    ['selectors' => ['Paper Type' => 'RC Satin'], 'price' => 60.00],
                ],
                'discount_rows' => [
                    ['min_quantity' => 10, 'price_reduction' => -20.00],
                ],
                'consumptions' => [
                    ['material' => 'RC Satin Poster Paper', 'quantity' => 1],
                    ['material' => 'CMYK Ink', 'quantity' => 1],
                    ['material' => 'Standard Trim Wear', 'quantity' => 1],
                ],
            ],
            [
                'name' => 'Photo-Paper Prints',
                'description' => '300gsm photo paper prints with lamination and optional custom cut.',
                'cover_image_path' => 'images/products/photo-paper-prints.jpg',
                'min_quantity' => 1,
                'layout_fee' => 0.00,
                'options' => [
                    ['label' => 'Lamination', 'types' => $laminationsWithNone],
                    ['label' => 'Print Side', 'types' => ['Single Side', 'Back to Back']],
                    ['label' => 'Cut Style', 'types' => ['Standard Cut', 'Custom Cut']],
                ],
                'pricing_rows' => $photoPaperRows,
                'discount_rows' => [],
                'consumptions' => array_merge(
                    [
                        ['material' => 'Photo Paper 300gsm', 'quantity' => 1],
                        ['material' => 'CMYK Ink', 'quantity' => 1],
                        ['material' => 'CMYK Ink', 'quantity' => 2, 'selector' => ['Print Side' => 'Back to Back']],
                        ['material' => 'Standard Trim Wear', 'quantity' => 1, 'selector' => ['Cut Style' => 'Standard Cut']],
                        ['material' => 'Custom Cut Blade Wear', 'quantity' => 2, 'selector' => ['Cut Style' => 'Custom Cut']],
                    ],
                    $this->buildLaminationSpecificConsumptions('Lamination')
                ),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $blueprint
     * @return array{0: Product, 1: array<string, array<string, int>>}
     */
    private function seedProductTemplate(array $blueprint): array
    {
        $product = Product::query()->updateOrCreate(
            ['slug' => Str::slug((string) $blueprint['name'])],
            [
                'name' => (string) $blueprint['name'],
                'description' => (string) $blueprint['description'],
                'cover_image_path' => (string) $blueprint['cover_image_path'],
            ]
        );

        $template = OrderTemplate::query()->firstOrCreate([
            'product_id' => $product->id,
        ]);

        /** @var array<string, array<string, int>> $optionTypeLookup */
        $optionTypeLookup = [];

        foreach ($blueprint['options'] as $optionIndex => $optionDefinition) {
            $option = OrderTemplateOption::query()->updateOrCreate(
                [
                    'order_template_id' => $template->id,
                    'label' => $optionDefinition['label'],
                ],
                [
                    'position' => $optionIndex + 1,
                ]
            );

            foreach ($optionDefinition['types'] as $typeIndex => $typeName) {
                $optionType = OrderTemplateOptionType::query()->updateOrCreate(
                    [
                        'order_template_option_id' => $option->id,
                        'type_name' => $typeName,
                    ],
                    [
                        'is_available' => true,
                        'position' => $typeIndex + 1,
                    ]
                );

                $optionTypeLookup[$optionDefinition['label']][$typeName] = (int) $optionType->id;
            }
        }

        foreach ($blueprint['pricing_rows'] as $pricingRow) {
            $combinationKey = $this->buildCombinationKeyFromSelectors(
                $pricingRow['selectors'],
                $optionTypeLookup,
                (string) $product->name
            );

            OrderTemplatePricing::query()->updateOrCreate(
                [
                    'order_template_id' => $template->id,
                    'combination_key' => $combinationKey,
                ],
                [
                    'price' => (float) $pricingRow['price'],
                ]
            );
        }

        foreach ($blueprint['discount_rows'] as $discountIndex => $discountRow) {
            OrderTemplateDiscount::query()->updateOrCreate(
                [
                    'order_template_id' => $template->id,
                    'min_quantity' => (int) $discountRow['min_quantity'],
                ],
                [
                    'price_reduction' => (float) $discountRow['price_reduction'],
                    'position' => $discountIndex + 1,
                ]
            );
        }

        $this->upsertMinOrder((int) $template->id, (int) $blueprint['min_quantity']);
        $this->upsertLayoutFee((int) $template->id, (float) $blueprint['layout_fee']);

        return [$product, $optionTypeLookup];
    }

    /**
     * @param array<string, Material> $materials
     * @param array<int, array<string, mixed>> $consumptionBlueprints
     */
    private function seedMaterialConsumptions(array $materials, array $consumptionBlueprints): void
    {
        $timestamp = now();

        foreach ($consumptionBlueprints as $blueprint) {
            $productId = (int) $blueprint['product_id'];
            $optionTypeLookup = $blueprint['option_type_lookup'];

            foreach ($blueprint['consumptions'] as $consumption) {
                $materialName = (string) $consumption['material'];
                $material = $materials[$materialName] ?? null;

                if (! $material) {
                    throw new RuntimeException("Material [{$materialName}] was not seeded before consumption mapping.");
                }

                $optionTypeId = null;
                if (array_key_exists('selector', $consumption)) {
                    $optionTypeId = $this->resolveOptionTypeIdFromSelector(
                        $consumption['selector'],
                        $optionTypeLookup,
                        $materialName
                    );
                }

                MaterialConsumption::query()->updateOrCreate(
                    [
                        'material_id' => $material->id,
                        'product_id' => $productId,
                        'order_template_option_type_id' => $optionTypeId,
                    ],
                    [
                        'quantity' => (int) $consumption['quantity'],
                    ]
                );

                if ($optionTypeId === null) {
                    DB::table('product_material')->updateOrInsert(
                        [
                            'product_id' => $productId,
                            'material_id' => $material->id,
                        ],
                        [
                            'quantity' => (int) $consumption['quantity'],
                            'updated_at' => $timestamp,
                            'created_at' => $timestamp,
                        ]
                    );
                }
            }
        }
    }

    private function seedRushFees(): void
    {
        $definitions = [
            [
                'label' => 'Orders Below P3000',
                'min_price' => 0.00,
                'max_price' => 2999.99,
                'image_url' => 'uploads/rush-fees/below-3000.png',
                'timeframes' => [
                    ['label' => '2 days', 'percentage' => 45.00],
                    ['label' => '3 - 4 days', 'percentage' => 35.00],
                    ['label' => '5 - 6 days', 'percentage' => 30.00],
                    ['label' => '7 days', 'percentage' => 25.00],
                    ['label' => '8 days minimum (may depend on order queue)', 'percentage' => 0.00],
                ],
            ],
            [
                'label' => 'Orders P3000 to P4000',
                'min_price' => 3000.00,
                'max_price' => 4000.00,
                'image_url' => 'uploads/rush-fees/3000-4000.png',
                'timeframes' => [
                    ['label' => '7 days', 'percentage' => 45.00],
                    ['label' => '8 - 9 days', 'percentage' => 35.00],
                    ['label' => '10 - 11 days', 'percentage' => 30.00],
                    ['label' => '12 days', 'percentage' => 25.00],
                    ['label' => '13 days minimum (may depend on order queue)', 'percentage' => 0.00],
                ],
            ],
            [
                'label' => 'Orders P4001 to P5000',
                'min_price' => 4001.00,
                'max_price' => 5000.00,
                'image_url' => 'uploads/rush-fees/4001-5000.png',
                'timeframes' => [
                    ['label' => '9 days', 'percentage' => 45.00],
                    ['label' => '10 - 11 days', 'percentage' => 35.00],
                    ['label' => '12 - 13 days', 'percentage' => 30.00],
                    ['label' => '14 days', 'percentage' => 25.00],
                    ['label' => '15 days minimum (may depend on order queue)', 'percentage' => 0.00],
                ],
            ],
            [
                'label' => 'Orders P5001 and Above',
                'min_price' => 5001.00,
                'max_price' => null,
                'image_url' => 'uploads/rush-fees/5001-above.png',
                'timeframes' => [
                    ['label' => '11 days', 'percentage' => 45.00],
                    ['label' => '12 - 13 days', 'percentage' => 35.00],
                    ['label' => '14 - 15 days', 'percentage' => 30.00],
                    ['label' => '16 days', 'percentage' => 25.00],
                    ['label' => '17 days minimum (may depend on order queue)', 'percentage' => 0.00],
                ],
            ],
        ];

        foreach ($definitions as $definition) {
            $rushFee = RushFee::query()->updateOrCreate(
                ['label' => $definition['label']],
                [
                    'min_price' => $definition['min_price'],
                    'max_price' => $definition['max_price'],
                    'image_url' => $definition['image_url'],
                ]
            );

            foreach ($definition['timeframes'] as $index => $timeframe) {
                RushFeeTimeframe::query()->updateOrCreate(
                    [
                        'rush_fee_id' => $rushFee->id,
                        'label' => $timeframe['label'],
                    ],
                    [
                        'percentage' => $timeframe['percentage'],
                        'sort_order' => $index + 1,
                    ]
                );
            }
        }
    }

    /**
     * @param array<string, float> $pricesByType
     * @return array<int, array{selectors: array<string, string>, price: float}>
     */
    private function buildSingleOptionPricingRows(string $optionLabel, array $pricesByType): array
    {
        $rows = [];

        foreach ($pricesByType as $typeName => $price) {
            $rows[] = [
                'selectors' => [$optionLabel => $typeName],
                'price' => (float) $price,
            ];
        }

        return $rows;
    }

    /**
     * @param array<string, array<string, float>> $matrix
     * @return array<int, array{selectors: array<string, string>, price: float}>
     */
    private function buildDoubleOptionPricingRows(string $firstOptionLabel, string $secondOptionLabel, array $matrix): array
    {
        $rows = [];

        foreach ($matrix as $firstOptionType => $secondOptionPrices) {
            foreach ($secondOptionPrices as $secondOptionType => $price) {
                $rows[] = [
                    'selectors' => [
                        $firstOptionLabel => $firstOptionType,
                        $secondOptionLabel => $secondOptionType,
                    ],
                    'price' => (float) $price,
                ];
            }
        }

        return $rows;
    }

    /**
     * @return array<int, array{material: string, quantity: int, selector?: array<string, string>}>
     */
    private function buildStickerConsumptions(string $bladeWearMaterial): array
    {
        return array_merge(
            [
                ['material' => 'Vinyl Waterproof Sheet', 'quantity' => 1],
                ['material' => 'CMYK Ink', 'quantity' => 1],
                ['material' => $bladeWearMaterial, 'quantity' => 1],
            ],
            $this->buildLaminationSpecificConsumptions('Lamination')
        );
    }

    /**
     * @param array<string, string> $sizeToMaterial
     * @return array<int, array{material: string, quantity: int, selector: array<string, string>}>
     */
    private function buildSizeSpecificConsumptions(array $sizeToMaterial): array
    {
        $rows = [];

        foreach ($sizeToMaterial as $size => $materialName) {
            $rows[] = [
                'material' => $materialName,
                'quantity' => 1,
                'selector' => ['Size' => $size],
            ];
        }

        return $rows;
    }

    /**
     * @return array<int, array{material: string, quantity: int, selector: array<string, string>}>
     */
    private function buildLaminationSpecificConsumptions(string $optionLabel): array
    {
        $rows = [];

        foreach (self::LAMINATION_MATERIAL_MAP as $lamination => $materialName) {
            $rows[] = [
                'material' => $materialName,
                'quantity' => 1,
                'selector' => [$optionLabel => $lamination],
            ];
        }

        return $rows;
    }

    /**
     * @param array<string, string> $selectors
     * @param array<string, array<string, int>> $optionTypeLookup
     */
    private function buildCombinationKeyFromSelectors(array $selectors, array $optionTypeLookup, string $productName): string
    {
        $typeIds = [];

        foreach ($selectors as $optionLabel => $typeName) {
            $typeId = $optionTypeLookup[$optionLabel][$typeName] ?? null;

            if (! $typeId) {
                throw new RuntimeException(
                    "Unable to resolve option type [{$optionLabel}: {$typeName}] while seeding product [{$productName}]."
                );
            }

            $typeIds[] = (int) $typeId;
        }

        sort($typeIds);

        return implode(',', $typeIds);
    }

    /**
     * @param array<string, string> $selector
     * @param array<string, array<string, int>> $optionTypeLookup
     */
    private function resolveOptionTypeIdFromSelector(array $selector, array $optionTypeLookup, string $materialName): ?int
    {
        if (count($selector) !== 1) {
            throw new RuntimeException(
                "Material consumption selector for [{$materialName}] must contain exactly one option key."
            );
        }

        $optionLabel = array_key_first($selector);
        $typeName = $selector[$optionLabel];

        $typeId = $optionTypeLookup[$optionLabel][$typeName] ?? null;

        if (! $typeId) {
            throw new RuntimeException(
                "Unable to resolve consumption selector [{$optionLabel}: {$typeName}] for material [{$materialName}]."
            );
        }

        return (int) $typeId;
    }

    private function upsertMinOrder(int $orderTemplateId, int $minQuantity): void
    {
        $existing = OrderTemplateMinOrder::query()
            ->where('order_template_id', $orderTemplateId)
            ->orderBy('id')
            ->get();

        if ($existing->isEmpty()) {
            OrderTemplateMinOrder::query()->create([
                'order_template_id' => $orderTemplateId,
                'min_quantity' => $minQuantity,
            ]);

            return;
        }

        $primary = $existing->first();
        $primary?->update(['min_quantity' => $minQuantity]);

        if ($existing->count() > 1) {
            OrderTemplateMinOrder::query()
                ->where('order_template_id', $orderTemplateId)
                ->where('id', '!=', $primary?->id)
                ->delete();
        }
    }

    private function upsertLayoutFee(int $orderTemplateId, float $feeAmount): void
    {
        $existing = OrderTemplateLayoutFee::query()
            ->where('order_template_id', $orderTemplateId)
            ->orderBy('id')
            ->get();

        if ($existing->isEmpty()) {
            OrderTemplateLayoutFee::query()->create([
                'order_template_id' => $orderTemplateId,
                'fee_amount' => $feeAmount,
            ]);

            return;
        }

        $primary = $existing->first();
        $primary?->update(['fee_amount' => $feeAmount]);

        if ($existing->count() > 1) {
            OrderTemplateLayoutFee::query()
                ->where('order_template_id', $orderTemplateId)
                ->where('id', '!=', $primary?->id)
                ->delete();
        }
    }
}
