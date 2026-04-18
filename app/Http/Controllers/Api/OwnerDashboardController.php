<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OwnerDashboardMetricsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OwnerDashboardController extends Controller
{
    public function __construct(
        private readonly OwnerDashboardMetricsService $metricsService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'year' => 'nullable|integer|min:1900|max:3000',
            'month' => 'nullable|integer|min:0|max:11',
            'report_period' => 'nullable|string|max:20',
            'sales_period' => 'nullable|string|max:20',
            'period' => 'nullable|string|max:20',
        ]);

        $metrics = $this->metricsService->build(
            array_key_exists('year', $validated) ? (int) $validated['year'] : null,
            array_key_exists('month', $validated) ? (int) $validated['month'] : null,
            array_key_exists('report_period', $validated) ? (string) $validated['report_period'] : null,
            array_key_exists('sales_period', $validated) ? (string) $validated['sales_period'] : null,
            array_key_exists('period', $validated) ? (string) $validated['period'] : null,
        );

        return response()->json([
            'success' => true,
            'data' => $metrics,
        ]);
    }
}
