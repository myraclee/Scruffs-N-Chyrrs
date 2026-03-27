<?php

namespace App\Http\Controllers\Api;

use App\Models\FAQ;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class FaqController extends Controller
{
    /**
     * Get all active FAQs (public endpoint)
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $faqs = FAQ::active()
            ->with('categoryRelation')
            ->get()
            ->groupBy(function ($faq) {
                return $faq->categoryRelation->name ?? 'Uncategorized';
            })
            ->map(function ($items) {
                return $items->values();
            });

        return response()->json([
            'success' => true,
            'data' => $faqs,
        ]);
    }

    /**
     * Get all FAQs (admin only - includes inactive)
     *
     * @return JsonResponse
     */
    public function adminIndex(): JsonResponse
    {
        $faqs = FAQ::orderBy('sort_order')
            ->with('categoryRelation')
            ->get()
            ->groupBy(function ($faq) {
                return $faq->categoryRelation->name ?? 'Uncategorized';
            })
            ->map(function ($items) {
                return $items->values();
            });

        return response()->json([
            'success' => true,
            'data' => $faqs,
        ]);
    }

    /**
     * Create a new FAQ
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'faq_category_id' => 'required|integer|exists:faq_categories,id',
            'question' => 'required|string|max:255',
            'answer' => 'required|string',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        // Determine sort_order if not provided
        if (!isset($validated['sort_order'])) {
            $maxSort = FAQ::where('faq_category_id', $validated['faq_category_id'])->max('sort_order');
            $validated['sort_order'] = ($maxSort ?? 0) + 1;
        }

        $faq = FAQ::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'FAQ created successfully',
            'data' => $faq,
        ], 201);
    }

    /**
     * Update an existing FAQ
     *
     * @param Request $request
     * @param FAQ $faq
     * @return JsonResponse
     */
    public function update(Request $request, FAQ $faq): JsonResponse
    {
        $validated = $request->validate([
            'faq_category_id' => 'nullable|integer|exists:faq_categories,id',
            'question' => 'nullable|string|max:255',
            'answer' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $faq->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'FAQ updated successfully',
            'data' => $faq,
        ]);
    }

    /**
     * Delete a FAQ
     *
     * @param FAQ $faq
     * @return JsonResponse
     */
    public function destroy(FAQ $faq): JsonResponse
    {
        $faq->delete();

        return response()->json([
            'success' => true,
            'message' => 'FAQ deleted successfully',
        ]);
    }

    /**
     * Reorder FAQs
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'faqs' => 'required|array',
            'faqs.*.id' => 'required|integer|exists:faqs,id',
            'faqs.*.sort_order' => 'required|integer|min:0',
        ]);

        foreach ($validated['faqs'] as $item) {
            FAQ::find($item['id'])->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'FAQs reordered successfully',
        ]);
    }
}
