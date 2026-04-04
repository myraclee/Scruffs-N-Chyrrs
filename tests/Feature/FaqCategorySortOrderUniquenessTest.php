<?php

namespace Tests\Feature;

use App\Models\FAQCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqCategorySortOrderUniquenessTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_rejects_duplicate_sort_order(): void
    {
        $owner = User::factory()->create([
            'user_type' => 'owner',
        ]);

        FAQCategory::create([
            'name' => 'Existing Category',
            'sort_order' => 5,
        ]);

        $response = $this
            ->actingAs($owner)
            ->postJson('/api/faq-categories', [
                'name' => 'New Category',
                'sort_order' => 5,
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['sort_order']);
    }

    public function test_update_rejects_duplicate_sort_order_on_different_category(): void
    {
        $owner = User::factory()->create([
            'user_type' => 'owner',
        ]);

        $first = FAQCategory::create([
            'name' => 'First Category',
            'sort_order' => 2,
        ]);

        $second = FAQCategory::create([
            'name' => 'Second Category',
            'sort_order' => 8,
        ]);

        $response = $this
            ->actingAs($owner)
            ->putJson("/api/faq-categories/{$second->id}", [
                'sort_order' => $first->sort_order,
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['sort_order']);
    }

    public function test_update_allows_keeping_same_sort_order_for_same_category(): void
    {
        $owner = User::factory()->create([
            'user_type' => 'owner',
        ]);

        $category = FAQCategory::create([
            'name' => 'Category To Edit',
            'sort_order' => 50,
        ]);

        $response = $this
            ->actingAs($owner)
            ->putJson("/api/faq-categories/{$category->id}", [
                'name' => 'Category To Edit Updated',
                'sort_order' => 50,
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.sort_order', 50)
            ->assertJsonPath('data.name', 'Category To Edit Updated');
    }
}
