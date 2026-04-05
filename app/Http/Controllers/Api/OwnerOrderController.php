<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerOrderGroup;
use App\Services\InventoryStockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OwnerOrderController extends Controller
{
    public function __construct(
        protected InventoryStockService $stockService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'nullable|in:all,waiting,approved,preparing,ready,completed,cancelled',
            'search' => 'nullable|string|max:120',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $perPage = (int) ($validated['per_page'] ?? 12);
        $status = $validated['status'] ?? 'all';
        $search = trim((string) ($validated['search'] ?? ''));

        $query = CustomerOrderGroup::query()
            ->with([
                'user:id,first_name,last_name,email,contact_number',
                'orders:id,customer_order_group_id,product_id,quantity,total_price,status,selected_options,special_instructions,created_at',
                'orders.product:id,name',
            ])
            ->latest();

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where(function ($inner) use ($search) {
                $inner->where('id', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $groups = $query->paginate($perPage);

        $payload = [
            'success' => true,
            'data' => $groups->getCollection()->map(fn ($group) => $this->transformGroup($group))->values(),
            'meta' => [
                'current_page' => $groups->currentPage(),
                'last_page' => $groups->lastPage(),
                'per_page' => $groups->perPage(),
                'total' => $groups->total(),
            ],
        ];

        return response()->json($payload);
    }

    public function show(CustomerOrderGroup $orderGroup): JsonResponse
    {
        $orderGroup->load([
            'user:id,first_name,last_name,email,contact_number',
            'orders.product:id,name',
            'orders.rushFee:id,label',
            'orders.orderTemplate.options.optionTypes:id,order_template_option_id,type_name,is_available,position',
            'orders.orderTemplate.options:id,order_template_id,label,position',
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->transformGroup($orderGroup, true),
        ]);
    }

    public function updateStatus(Request $request, CustomerOrderGroup $orderGroup): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:waiting,approved,preparing,ready,completed,cancelled',
        ]);

        $nextStatus = $validated['status'];

        if (!$orderGroup->canTransitionTo($nextStatus)) {
            return response()->json([
                'success' => false,
                'message' => "Cannot move order from {$orderGroup->status} to {$nextStatus}.",
            ], 422);
        }

        $shouldRestock = $orderGroup->shouldRestockOnCancellation($nextStatus);

        DB::transaction(function () use ($orderGroup, $nextStatus, $shouldRestock) {
            $orderGroup->update(['status' => $nextStatus]);
            $orderGroup->orders()->update(['status' => $nextStatus]);

            if ($shouldRestock) {
                $requirements = $orderGroup->inventory_material_requirements ?? [];
                $this->stockService->restoreFromRequirements($requirements);
                $orderGroup->update(['inventory_restored_at' => now()]);
            }
        });

        $orderGroup->refresh()->load([
            'user:id,first_name,last_name,email,contact_number',
            'orders.product:id,name',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully.',
            'data' => $this->transformGroup($orderGroup),
        ]);
    }

    private function transformGroup(CustomerOrderGroup $group, bool $withFullOrderPayload = false): array
    {
        $orders = $group->orders->map(function ($order) use ($withFullOrderPayload) {
            $base = [
                'id' => $order->id,
                'product_name' => $order->product?->name,
                'quantity' => $order->quantity,
                'total_price' => (float) $order->total_price,
                'status' => $order->status,
                'created_at' => $order->created_at?->toISOString(),
            ];

            if (!$withFullOrderPayload) {
                return $base;
            }

            return array_merge($base, [
                'selected_options' => $order->selected_options,
                'formatted_options' => $order->formatted_options,
                'special_instructions' => $order->special_instructions,
                'base_price' => (float) $order->base_price,
                'discount_amount' => (float) $order->discount_amount,
                'rush_fee_amount' => (float) $order->rush_fee_amount,
                'layout_fee_amount' => (float) $order->layout_fee_amount,
            ]);
        })->values();

        return [
            'id' => $group->id,
            'status' => $group->status,
            'status_label' => $group->status_label,
            'general_drive_link' => $group->general_drive_link,
            'user' => [
                'id' => $group->user?->id,
                'name' => trim(($group->user?->first_name ?? '').' '.($group->user?->last_name ?? '')),
                'email' => $group->user?->email,
                'contact_number' => $group->user?->contact_number,
            ],
            'totals' => [
                'subtotal_price' => (float) $group->subtotal_price,
                'discount_total' => (float) $group->discount_total,
                'rush_fee_total' => (float) $group->rush_fee_total,
                'layout_fee_total' => (float) $group->layout_fee_total,
                'total_price' => (float) $group->total_price,
            ],
            'items_count' => $orders->count(),
            'orders' => $orders,
            'created_at' => $group->created_at?->toISOString(),
            'updated_at' => $group->updated_at?->toISOString(),
        ];
    }
}
