<?php

namespace Tests\Feature;

use App\Models\FAQ;
use App\Models\FAQCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqSortOrderConsistencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_faq_endpoint_orders_categories_by_category_sort_order(): void
    {
        $firstCategory = FAQCategory::create([
            'name' => 'Category One',
            'sort_order' => 1,
        ]);

        $secondCategory = FAQCategory::create([
            'name' => 'Category Two',
            'sort_order' => 2,
        ]);

        $customCategory = FAQCategory::create([
            'name' => 'Custom Category',
            'sort_order' => 5,
        ]);

        // Intentionally give the custom category the lowest FAQ sort order
        // to reproduce the reported mismatch scenario.
        FAQ::create([
            'faq_category_id' => $customCategory->id,
            'question' => 'Custom question',
            'answer' => 'Custom answer',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        FAQ::create([
            'faq_category_id' => $firstCategory->id,
            'question' => 'First category question',
            'answer' => 'First category answer',
            'sort_order' => 3,
            'is_active' => true,
        ]);

        FAQ::create([
            'faq_category_id' => $secondCategory->id,
            'question' => 'Second category question',
            'answer' => 'Second category answer',
            'sort_order' => 4,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/faqs');

        $response->assertOk()->assertJsonPath('success', true);

        $categoryOrder = array_keys($response->json('data'));

        $this->assertSame(
            ['Category One', 'Category Two', 'Custom Category'],
            $categoryOrder,
            'Public FAQ category order must follow faq_categories.sort_order.',
        );
    }

    public function test_public_faq_endpoint_orders_faqs_within_a_category_by_faq_sort_order(): void
    {
        $category = FAQCategory::create([
            'name' => 'Ordering Category',
            'sort_order' => 1,
        ]);

        FAQ::create([
            'faq_category_id' => $category->id,
            'question' => 'Question 5',
            'answer' => 'Answer 5',
            'sort_order' => 5,
            'is_active' => true,
        ]);

        FAQ::create([
            'faq_category_id' => $category->id,
            'question' => 'Question 1',
            'answer' => 'Answer 1',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        FAQ::create([
            'faq_category_id' => $category->id,
            'question' => 'Question 3',
            'answer' => 'Answer 3',
            'sort_order' => 3,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/faqs');

        $response->assertOk()->assertJsonPath('success', true);

        $faqRows = $response->json('data.Ordering Category');
        $sortOrders = array_map(fn (array $faq) => (int) $faq['sort_order'], $faqRows ?? []);

        $this->assertSame([1, 3, 5], $sortOrders, 'FAQs inside each category must be ordered by faqs.sort_order.');
    }

    public function test_admin_faq_endpoint_uses_same_category_order_contract(): void
    {
        $user = User::factory()->create();

        $firstCategory = FAQCategory::create([
            'name' => 'Admin Category One',
            'sort_order' => 1,
        ]);

        $secondCategory = FAQCategory::create([
            'name' => 'Admin Category Two',
            'sort_order' => 2,
        ]);

        $lastCategory = FAQCategory::create([
            'name' => 'Admin Category Last',
            'sort_order' => 5,
        ]);

        FAQ::create([
            'faq_category_id' => $lastCategory->id,
            'question' => 'Inactive last category question',
            'answer' => 'Inactive answer',
            'sort_order' => 1,
            'is_active' => false,
        ]);

        FAQ::create([
            'faq_category_id' => $firstCategory->id,
            'question' => 'Admin first category question',
            'answer' => 'Admin first category answer',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        FAQ::create([
            'faq_category_id' => $secondCategory->id,
            'question' => 'Admin second category question',
            'answer' => 'Admin second category answer',
            'sort_order' => 3,
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->getJson('/api/faqs/admin/index');

        $response->assertOk()->assertJsonPath('success', true);

        $categoryOrder = array_keys($response->json('data'));

        $this->assertSame(
            ['Admin Category One', 'Admin Category Two', 'Admin Category Last'],
            $categoryOrder,
            'Admin FAQ category order must follow faq_categories.sort_order.',
        );
    }
}
