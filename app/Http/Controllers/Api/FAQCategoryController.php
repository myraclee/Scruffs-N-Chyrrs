<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FAQCategory;
use App\Services\CategorySortOrderManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FAQCategoryController extends Controller
{
    protected CategorySortOrderManager $sortOrderManager;

    public function __construct(CategorySortOrderManager $sortOrderManager)
    {
        $this->sortOrderManager = $sortOrderManager;
    }

    /**
     * Get all FAQ categories (admin only)
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $categories = FAQCategory::ordered()->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * Create a new FAQ category
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:faq_categories,name',
            'sort_order' => 'required|integer|min:1|max:99',
        ], [
            'name.unique' => 'A category with this name already exists.',
            'sort_order.min' => 'Sort order must be at least 1.',
            'sort_order.max' => 'Sort order cannot exceed 99.',
        ]);

        $category = FAQCategory::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully',
            'data' => $category,
        ], 201);
    }

    /**
     * Update an existing FAQ category
     *
     * @param Request $request
     * @param FAQCategory $faqCategory
     * @return JsonResponse
     */
    public function update(Request $request, FAQCategory $faqCategory): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255|unique:faq_categories,name,' . $faqCategory->id,
            'sort_order' => 'nullable|integer|min:1|max:99',
        ], [
            'name.unique' => 'A category with this name already exists.',
            'sort_order.min' => 'Sort order must be at least 1.',
            'sort_order.max' => 'Sort order cannot exceed 99.',
        ]);

        $faqCategory->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully',
            'data' => $faqCategory,
        ]);
    }

    /**
     * Delete a FAQ category
     *
     * Returns 409 Conflict if category has associated FAQs
     *
     * @param FAQCategory $faqCategory
     * @return JsonResponse
     */
    public function destroy(FAQCategory $faqCategory): JsonResponse
    {
        // Check if category has any FAQs
        if ($faqCategory->faqs()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete category that contains FAQs. Please delete or move the FAQs first.',
                'faq_count' => $faqCategory->faqs()->count(),
            ], 409);
        }

        $faqCategory->delete();

        // Compact sort order after deletion
        $this->sortOrderManager->compactSortOrder();

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully',
        ]);
    }
}
