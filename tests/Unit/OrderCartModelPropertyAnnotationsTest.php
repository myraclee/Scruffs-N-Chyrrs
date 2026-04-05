<?php

namespace Tests\Unit;

use App\Models\CustomerCart;
use App\Models\CustomerCartItem;
use App\Models\CustomerOrder;
use App\Models\CustomerOrderGroup;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use Tests\TestCase;

class OrderCartModelPropertyAnnotationsTest extends TestCase
{
    /**
     * @param class-string $modelClass
     * @param array<int, string> $requiredSnippets
     */
    #[DataProvider('annotationRequirementsProvider')]
    public function test_model_has_expected_annotation_snippets(string $modelClass, array $requiredSnippets): void
    {
        $reflection = new ReflectionClass($modelClass);
        $docComment = $reflection->getDocComment();

        $this->assertNotFalse($docComment, "{$modelClass} is missing a class-level PHPDoc block.");

        foreach ($requiredSnippets as $snippet) {
            $this->assertStringContainsString(
                $snippet,
                (string) $docComment,
                "{$modelClass} is missing expected annotation snippet: {$snippet}"
            );
        }
    }

    /**
     * @return array<string, array{0: class-string, 1: array<int, string>}>
     */
    public static function annotationRequirementsProvider(): array
    {
        return [
            'customer_cart' => [
                CustomerCart::class,
                [
                    '@property int $id',
                    '@property int $user_id',
                    '@property string $status',
                    '@property-read User $user',
                ],
            ],
            'customer_cart_item' => [
                CustomerCartItem::class,
                [
                    '@property int $id',
                    '@property int $product_id',
                    '@property int $order_template_id',
                    '@property int|null $rush_fee_id',
                    '@property int $quantity',
                    '@property string|null $special_instructions',
                    '@property \\Illuminate\\Support\\Carbon $created_at',
                ],
            ],
            'customer_order' => [
                CustomerOrder::class,
                [
                    '@property int $id',
                    '@property int $product_id',
                    '@property int $quantity',
                    '@property string $status',
                    '@property \Illuminate\Support\Carbon $created_at',
                    '@property-read string $status_label',
                    '@property-read array<int, array<string, string>> $formatted_options',
                    '@property string|null $special_instructions',
                ],
            ],
            'customer_order_group' => [
                CustomerOrderGroup::class,
                [
                    '@property int $id',
                    '@property int $user_id',
                    '@property string $status',
                    '@property string|null $general_drive_link',
                    '@property \Illuminate\Support\Carbon $created_at',
                    '@property \Illuminate\Support\Carbon $updated_at',
                    '@property-read string $status_label',
                ],
            ],
        ];
    }
}
