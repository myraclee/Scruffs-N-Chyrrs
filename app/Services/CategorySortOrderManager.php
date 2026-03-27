<?php

namespace App\Services;

use App\Models\FAQCategory;
use Illuminate\Support\Facades\DB;

/**
 * CategorySortOrderManager
 *
 * Handles sort order management for FAQ categories.
 * Ensures sort orders remain in sequence (1, 2, 3, ...) without gaps.
 */
class CategorySortOrderManager
{
    /**
     * Validate if a sort order value is valid
     *
     * @param mixed $order
     * @return bool
     */
    public function validateSortOrder($order): bool
    {
        return is_numeric($order) && $order >= 1 && $order <= 99;
    }

    /**
     * Compact sort orders to remove gaps after deletion
     * Renumbers all categories to be 1, 2, 3, ... N
     *
     * @return void
     */
    public function compactSortOrder(): void
    {
        $categories = FAQCategory::ordered()->get();

        DB::transaction(function () use ($categories) {
            $newSortOrder = 1;

            foreach ($categories as $category) {
                $category->update(['sort_order' => $newSortOrder]);
                $newSortOrder++;
            }
        });
    }

    /**
     * Move a category to a new sort order and compact others
     *
     * @param int $categoryId
     * @param int $newSortOrder
     * @return void
     * @throws \InvalidArgumentException
     */
    public function moveCategoryToOrder(int $categoryId, int $newSortOrder): void
    {
        if (!$this->validateSortOrder($newSortOrder)) {
            throw new \InvalidArgumentException("Sort order must be between 1 and 99.");
        }

        DB::transaction(function () use ($categoryId, $newSortOrder) {
            $category = FAQCategory::findOrFail($categoryId);
            $currentSort = $category->sort_order;

            // If moving to same position, do nothing
            if ($currentSort === $newSortOrder) {
                return;
            }

            // Shift other categories
            if ($newSortOrder > $currentSort) {
                // Moving down: shift categories up
                FAQCategory::whereBetween('sort_order', [$currentSort + 1, $newSortOrder])
                    ->decrement('sort_order');
            } else {
                // Moving up: shift categories down
                FAQCategory::whereBetween('sort_order', [$newSortOrder, $currentSort - 1])
                    ->increment('sort_order');
            }

            // Update the category
            $category->update(['sort_order' => $newSortOrder]);
        });

        // Final compaction to ensure no gaps
        $this->compactSortOrder();
    }
}
