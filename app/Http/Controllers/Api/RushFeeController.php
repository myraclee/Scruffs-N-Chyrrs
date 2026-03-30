<?php

namespace App\Http\Controllers\Api;

use App\Models\RushFee;
use App\Models\RushFeeTimeframe;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class RushFeeController extends Controller
{
    /**
     * Get all rush fees with timeframes (public endpoint)
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $rushFees = RushFee::ordered()
            ->with('timeframes')
            ->get()
            ->map(function ($fee) {
                return [
                    'id' => $fee->id,
                    'label' => $fee->label,
                    'min_price' => $fee->min_price,
                    'max_price' => $fee->max_price,
                    'timeframes' => $fee->timeframes->sortBy('sort_order')->values(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $rushFees,
        ]);
    }

    /**
     * Get all rush fees (admin only - full detail)
     *
     * @return JsonResponse
     */
    public function adminIndex(): JsonResponse
    {
        $rushFees = RushFee::ordered()
            ->with('timeframes')
            ->get()
            ->map(function ($fee) {
                return [
                    'id' => $fee->id,
                    'label' => $fee->label,
                    'min_price' => $fee->min_price,
                    'max_price' => $fee->max_price,
                    'timeframes' => $fee->timeframes->sortBy('sort_order')->values(),
                    'created_at' => $fee->created_at,
                    'updated_at' => $fee->updated_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $rushFees,
        ]);
    }

    /**
     * Create a new rush fee with timeframes
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'label' => 'required|string|max:255',
                'min_price' => 'required|numeric|min:0',
                'max_price' => 'required|numeric|min:0|gt:min_price',
                'timeframes' => 'required|array|min:1',
                'timeframes.*.label' => 'required|string|max:255',
                'timeframes.*.percentage' => 'required|numeric|min:0|max:100',
            ]);

            $rushFee = RushFee::create([
                'label' => $validated['label'],
                'min_price' => $validated['min_price'],
                'max_price' => $validated['max_price'],
            ]);

            // Create timeframes
            foreach ($validated['timeframes'] as $index => $timeframe) {
                RushFeeTimeframe::create([
                    'rush_fee_id' => $rushFee->id,
                    'label' => $timeframe['label'],
                    'percentage' => $timeframe['percentage'],
                    'sort_order' => $index,
                ]);
            }

            $rushFee->load('timeframes');

            return response()->json([
                'success' => true,
                'message' => 'Rush fee created successfully',
                'data' => $rushFee,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create rush fee',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update an existing rush fee with timeframes
     *
     * @param Request $request
     * @param RushFee $rushFee
     * @return JsonResponse
     */
    public function update(Request $request, RushFee $rushFee): JsonResponse
    {
        try {
            $validated = $request->validate([
                'label' => 'nullable|string|max:255',
                'min_price' => 'nullable|numeric|min:0',
                'max_price' => 'nullable|numeric|min:0',
                'timeframes' => 'nullable|array|min:1',
                'timeframes.*.id' => 'nullable|integer|exists:rush_fee_timeframes,id',
                'timeframes.*.label' => 'required|string|max:255',
                'timeframes.*.percentage' => 'required|numeric|min:0|max:100',
            ]);

            // Update main rush fee fields
            if (isset($validated['label'])) {
                $rushFee->label = $validated['label'];
            }
            if (isset($validated['min_price'])) {
                $rushFee->min_price = $validated['min_price'];
            }
            if (isset($validated['max_price'])) {
                $rushFee->max_price = $validated['max_price'];
            }
            $rushFee->save();

            // Update timeframes if provided
            if (isset($validated['timeframes'])) {
                // Delete timeframes not in the request
                $providedIds = collect($validated['timeframes'])
                    ->pluck('id')
                    ->filter()
                    ->toArray();

                RushFeeTimeframe::where('rush_fee_id', $rushFee->id)
                    ->whereNotIn('id', $providedIds)
                    ->delete();

                // Create or update timeframes
                foreach ($validated['timeframes'] as $index => $timeframe) {
                    if (isset($timeframe['id'])) {
                        RushFeeTimeframe::find($timeframe['id'])->update([
                            'label' => $timeframe['label'],
                            'percentage' => $timeframe['percentage'],
                            'sort_order' => $index,
                        ]);
                    } else {
                        RushFeeTimeframe::create([
                            'rush_fee_id' => $rushFee->id,
                            'label' => $timeframe['label'],
                            'percentage' => $timeframe['percentage'],
                            'sort_order' => $index,
                        ]);
                    }
                }
            }

            $rushFee->load('timeframes');

            return response()->json([
                'success' => true,
                'message' => 'Rush fee updated successfully',
                'data' => $rushFee,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update rush fee',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a rush fee (cascades to timeframes)
     *
     * @param RushFee $rushFee
     * @return JsonResponse
     */
    public function destroy(RushFee $rushFee): JsonResponse
    {
        try {
            $rushFee->delete();

            return response()->json([
                'success' => true,
                'message' => 'Rush fee deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete rush fee',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reorder timeframes within a rush fee
     *
     * @param Request $request
     * @param RushFee $rushFee
     * @return JsonResponse
     */
    public function reorderTimeframes(Request $request, RushFee $rushFee): JsonResponse
    {
        try {
            $validated = $request->validate([
                'timeframes' => 'required|array',
                'timeframes.*.id' => 'required|integer|exists:rush_fee_timeframes,id',
                'timeframes.*.sort_order' => 'required|integer|min:0',
            ]);

            foreach ($validated['timeframes'] as $item) {
                RushFeeTimeframe::find($item['id'])->update([
                    'sort_order' => $item['sort_order'],
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Timeframes reordered successfully',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reorder timeframes',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
