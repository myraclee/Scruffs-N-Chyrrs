<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Exceptions\InsufficientMaterialStockException;
use App\Exceptions\InvalidInventoryConfigurationException;
use App\Models\CustomerOrderGroup;
use App\Models\RushFee;
use App\Rules\GoogleDriveUrl;
use App\Services\InventoryStockService;
use App\Services\OrderPricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OwnerOrderController extends Controller
{
    public function __construct(
        protected OrderPricingService $pricingService,
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

        return response()->json([
            'success' => true,
            'data' => $groups->getCollection()->map(fn ($group) => $this->transformGroup($group))->values(),
            'meta' => [
                'current_page' => $groups->currentPage(),
                'last_page' => $groups->lastPage(),
                'per_page' => $groups->perPage(),
                'total' => $groups->total(),
            ],
        ]);
    }

    public function show(CustomerOrderGroup $orderGroup): JsonResponse
    {
        $orderGroup->load([
            'user:id,first_name,last_name,email,contact_number',
            'orders.product:id,name',
            'orders.rushFee:id,label',
            'orders.orderTemplate.minOrder:id,order_template_id,min_quantity',
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

        if (
            $orderGroup->status === 'approved'
            && $nextStatus === 'preparing'
            && $orderGroup->payment_status !== 'payment_received'
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot move to preparing until payment is confirmed.',
                'error_code' => 'owner_payment_confirmation_required',
            ], 422);
        }

        if (!$orderGroup->canTransitionTo($nextStatus)) {
            return response()->json([
                'success' => false,
                'message' => "Cannot move order from {$orderGroup->status} to {$nextStatus}.",
            ], 422);
        }

        $shouldRestock = $orderGroup->shouldRestockOnCancellation($nextStatus);
        $shouldSetOwnerDeclineReason = $nextStatus === 'cancelled' && $orderGroup->canOwnerDecline();

        DB::transaction(function () use ($orderGroup, $nextStatus, $shouldRestock, $shouldSetOwnerDeclineReason) {
            $groupUpdates = [
                'status' => $nextStatus,
            ];

            if ($nextStatus === 'cancelled') {
                $groupUpdates['payment_status'] = 'payment_cancelled';

                if ($shouldSetOwnerDeclineReason) {
                    $groupUpdates['cancellation_reason'] = 'owner_declined';
                }
            }

            $orderGroup->update($groupUpdates);
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

    public function confirmPayment(Request $request, CustomerOrderGroup $orderGroup): JsonResponse
    {
        $validated = $request->validate([
            'payment_confirmation_note' => 'nullable|string|max:1000',
        ]);

        if (! $orderGroup->canConfirmPayment()) {
            return response()->json([
                'success' => false,
                'message' => 'Payment can only be confirmed for approved orders waiting for payment confirmation.',
                'error_code' => 'owner_payment_confirm_not_allowed',
            ], 422);
        }

        DB::transaction(function () use ($orderGroup, $validated): void {
            $orderGroup->update([
                'payment_status' => 'payment_received',
                'payment_confirmed_at' => now(),
                'payment_confirmed_by' => Auth::id(),
                'payment_confirmation_note' => $validated['payment_confirmation_note'] ?? null,
                'status' => 'preparing',
            ]);

            $orderGroup->orders()->update([
                'status' => 'preparing',
            ]);
        });

        $orderGroup->refresh()->load([
            'user:id,first_name,last_name,email,contact_number',
            'orders.product:id,name',
            'orders.rushFee:id,label',
            'orders.orderTemplate.minOrder:id,order_template_id,min_quantity',
            'orders.orderTemplate.options.optionTypes:id,order_template_option_id,type_name,is_available,position',
            'orders.orderTemplate.options:id,order_template_id,label,position',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment confirmed. Order moved to preparing.',
            'data' => $this->transformGroup($orderGroup, true),
        ]);
    }

    public function updateDetails(Request $request, CustomerOrderGroup $orderGroup): JsonResponse
    {
        if (in_array($orderGroup->status, ['completed', 'cancelled'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Completed or cancelled orders cannot be edited.',
            ], 422);
        }

        $this->normalizeGeneralDriveLink($request);

        $validated = $request->validate([
            'general_drive_link' => ['sometimes', 'nullable', 'string', 'max:2048', new GoogleDriveUrl()],
            'orders' => 'required|array|min:1',
            'orders.*.id' => 'required|integer',
            'orders.*.selected_options' => 'required|array|min:1',
            'orders.*.quantity' => 'required|integer|min:1',
            'orders.*.rush_fee_id' => 'nullable|exists:rush_fees,id',
            'orders.*.special_instructions' => 'nullable|string|max:1000',
        ]);

        $orderGroup->load([
            'orders.product:id,name',
            'orders.rushFee:id,label',
            'orders.orderTemplate.minOrder:id,order_template_id,min_quantity',
            'orders.orderTemplate.options.optionTypes:id,order_template_option_id,type_name,is_available,position',
            'orders.orderTemplate.options:id,order_template_id,label,position',
            'orders.orderTemplate.pricings:id,order_template_id,combination_key,price',
            'orders.orderTemplate.discounts:id,order_template_id,min_quantity,price_reduction',
            'orders.orderTemplate.layoutFee:id,order_template_id,fee_amount',
            'user:id,first_name,last_name,email,contact_number',
        ]);

        $groupOrders = $orderGroup->orders->keyBy(fn ($order) => (int) $order->id);
        $requestedOrders = collect($validated['orders']);

        $duplicateIds = $requestedOrders
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->duplicates();

        if ($duplicateIds->isNotEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Duplicate order entries were provided.',
            ], 422);
        }

        $unknownOrderIds = $requestedOrders
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->reject(fn (int $id): bool => $groupOrders->has($id))
            ->values();

        if ($unknownOrderIds->isNotEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'One or more orders do not belong to this group.',
            ], 422);
        }

        $preparedUpdates = [];

        foreach ($requestedOrders as $orderPayload) {
            $order = $groupOrders->get((int) $orderPayload['id']);
            $template = $order?->orderTemplate;

            if (! $order || ! $template) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order template configuration is missing for one or more items.',
                ], 422);
            }

            $normalizedOptions = $this->pricingService->normalizeSelectedOptions(
                $template,
                $orderPayload['selected_options']
            );

            $quantity = (int) $orderPayload['quantity'];
            $minOrder = (int) ($template->minOrder?->min_quantity ?? 1);

            if ($quantity < $minOrder) {
                return response()->json([
                    'success' => false,
                    'message' => "Minimum order quantity is {$minOrder} for {$order->product?->name}.",
                ], 422);
            }

            $pricing = $this->pricingService->calculate(
                $template,
                $normalizedOptions,
                $quantity,
                $orderPayload['rush_fee_id'] ?? null,
                $orderPayload['special_instructions'] ?? null,
            );

            if (! ($pricing['success'] ?? false)) {
                return response()->json([
                    'success' => false,
                    'message' => $pricing['message'] ?? 'Unable to calculate updated order pricing.',
                ], 422);
            }

            $preparedUpdates[(int) $order->id] = [
                'selected_options' => $normalizedOptions,
                'quantity' => $quantity,
                'rush_fee_id' => $orderPayload['rush_fee_id'] ?? null,
                'special_instructions' => $orderPayload['special_instructions'] ?? null,
                'base_price' => $pricing['base_price'],
                'discount_amount' => $pricing['discount_amount'],
                'rush_fee_amount' => $pricing['rush_fee_amount'],
                'layout_fee_amount' => $pricing['layout_fee_amount'],
                'total_price' => $pricing['total_price'],
            ];
        }

        $finalOrderState = $orderGroup->orders->map(function ($order) use ($preparedUpdates): array {
            $update = $preparedUpdates[(int) $order->id] ?? null;

            return [
                'order_id' => (int) $order->id,
                'product_id' => (int) $order->product_id,
                'selected_options' => $update['selected_options'] ?? ($order->selected_options ?? []),
                'quantity' => (int) ($update['quantity'] ?? $order->quantity),
                'base_price' => (float) ($update['base_price'] ?? $order->base_price),
                'discount_amount' => (float) ($update['discount_amount'] ?? $order->discount_amount),
                'rush_fee_amount' => (float) ($update['rush_fee_amount'] ?? $order->rush_fee_amount),
                'layout_fee_amount' => (float) ($update['layout_fee_amount'] ?? $order->layout_fee_amount),
                'total_price' => (float) ($update['total_price'] ?? $order->total_price),
            ];
        })->values();

        $updatedOrderLines = $finalOrderState
            ->map(fn (array $line): array => [
                'product_id' => (int) $line['product_id'],
                'quantity' => (int) $line['quantity'],
                'selected_options' => $line['selected_options'],
            ])
            ->all();

        try {
            $newRequirements = $this->stockService->calculateRequirements($updatedOrderLines);
            [$deductionRequirements, $restoreRequirements] = $this->buildRequirementDelta(
                $orderGroup->inventory_material_requirements ?? [],
                $newRequirements
            );

            DB::transaction(function () use (
                $orderGroup,
                $groupOrders,
                $preparedUpdates,
                $finalOrderState,
                $newRequirements,
                $deductionRequirements,
                $restoreRequirements,
                $validated
            ) {
                if (! empty($deductionRequirements)) {
                    $this->stockService->deductFromRequirements($deductionRequirements);
                }

                if (! empty($restoreRequirements)) {
                    $this->stockService->restoreFromRequirements($restoreRequirements);
                }

                foreach ($preparedUpdates as $orderId => $payload) {
                    $groupOrders->get($orderId)?->update($payload);
                }

                $groupUpdates = [
                    'subtotal_price' => round((float) $finalOrderState->sum('base_price'), 2),
                    'discount_total' => round((float) $finalOrderState->sum('discount_amount'), 2),
                    'rush_fee_total' => round((float) $finalOrderState->sum('rush_fee_amount'), 2),
                    'layout_fee_total' => round((float) $finalOrderState->sum('layout_fee_amount'), 2),
                    'total_price' => round((float) $finalOrderState->sum('total_price'), 2),
                    'inventory_material_requirements' => $newRequirements,
                    'inventory_deducted_at' => $orderGroup->inventory_deducted_at ?? now(),
                    'inventory_restored_at' => null,
                ];

                if (array_key_exists('general_drive_link', $validated)) {
                    $groupUpdates['general_drive_link'] = $validated['general_drive_link'];
                }

                $orderGroup->update($groupUpdates);
            });
        } catch (InsufficientMaterialStockException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to apply changes because inventory stock is insufficient.',
                'shortages' => $e->shortages,
            ], 422);
        } catch (InvalidInventoryConfigurationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'configuration_issues' => $e->issues,
            ], 422);
        } catch (\Throwable $e) {
            logger()->error('Owner order details update failed', [
                'order_group_id' => $orderGroup->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to update order details. Please try again.',
            ], 500);
        }

        $orderGroup->refresh()->load([
            'user:id,first_name,last_name,email,contact_number',
            'orders.product:id,name',
            'orders.rushFee:id,label',
            'orders.orderTemplate.minOrder:id,order_template_id,min_quantity',
            'orders.orderTemplate.options.optionTypes:id,order_template_option_id,type_name,is_available,position',
            'orders.orderTemplate.options:id,order_template_id,label,position',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order details updated successfully.',
            'data' => $this->transformGroup($orderGroup, true),
        ]);
    }

    private function transformGroup(CustomerOrderGroup $group, bool $withFullOrderPayload = false): array
    {
        $orders = $group->orders->map(function ($order) use ($withFullOrderPayload) {
            $base = [
                'id' => $order->id,
                'product_id' => $order->product_id,
                'order_template_id' => $order->order_template_id,
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
                'rush_fee_id' => $order->rush_fee_id,
                'rush_fee_label' => $order->rushFee?->label,
                'selected_options' => $order->selected_options,
                'formatted_options' => $order->formatted_options,
                'special_instructions' => $order->special_instructions,
                'base_price' => (float) $order->base_price,
                'discount_amount' => (float) $order->discount_amount,
                'rush_fee_amount' => (float) $order->rush_fee_amount,
                'layout_fee_amount' => (float) $order->layout_fee_amount,
                'min_order_quantity' => (int) ($order->orderTemplate?->minOrder?->min_quantity ?? 1),
                'option_schema' => $this->buildOptionSchema($order),
            ]);
        })->values();

        return [
            'id' => $group->id,
            'status' => $group->status,
            'status_label' => $group->status_label,
            'payment_status' => (string) $group->payment_status,
            'payment_status_label' => $group->payment_status_label,
            'cancellation_reason' => $group->cancellation_reason,
            'general_drive_link' => $group->general_drive_link,
            'payment_method' => $group->payment_method,
            'payment_reference_number' => $group->payment_reference_number,
            'payment_proof_url' => $group->payment_proof_path
                ? asset('storage/'.$group->payment_proof_path)
                : null,
            'payment_submitted_at' => $group->payment_submitted_at?->toISOString(),
            'payment_confirmed_at' => $group->payment_confirmed_at?->toISOString(),
            'can_confirm_payment' => $group->canConfirmPayment(),
            'can_owner_decline' => $group->canOwnerDecline(),
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
            'rush_fee_options' => $withFullOrderPayload ? $this->buildRushFeeOptions() : [],
            'created_at' => $group->created_at?->toISOString(),
            'updated_at' => $group->updated_at?->toISOString(),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $previousRequirements
     * @param array<int, array<string, mixed>> $newRequirements
     * @return array{0: array<int, array<string, int|string>>, 1: array<int, array<string, int|string>>}
     */
    private function buildRequirementDelta(array $previousRequirements, array $newRequirements): array
    {
        $mapByMaterial = static function (array $requirements): array {
            $mapped = [];

            foreach ($requirements as $requirement) {
                $materialId = (int) ($requirement['material_id'] ?? 0);
                if ($materialId <= 0) {
                    continue;
                }

                $mapped[$materialId] = [
                    'material_id' => $materialId,
                    'material_name' => (string) ($requirement['material_name'] ?? 'Unknown Material'),
                    'required' => (int) ($requirement['required'] ?? 0),
                ];
            }

            return $mapped;
        };

        $previousMap = $mapByMaterial($previousRequirements);
        $newMap = $mapByMaterial($newRequirements);

        $materialIds = array_values(array_unique(array_merge(
            array_keys($previousMap),
            array_keys($newMap)
        )));

        $deductions = [];
        $restores = [];

        foreach ($materialIds as $materialId) {
            $oldRequired = (int) ($previousMap[$materialId]['required'] ?? 0);
            $newRequired = (int) ($newMap[$materialId]['required'] ?? 0);
            $materialName = (string) ($newMap[$materialId]['material_name'] ?? $previousMap[$materialId]['material_name'] ?? 'Unknown Material');

            if ($newRequired > $oldRequired) {
                $deductions[] = [
                    'material_id' => (int) $materialId,
                    'material_name' => $materialName,
                    'required' => $newRequired - $oldRequired,
                ];
                continue;
            }

            if ($oldRequired > $newRequired) {
                $restores[] = [
                    'material_id' => (int) $materialId,
                    'material_name' => $materialName,
                    'required' => $oldRequired - $newRequired,
                ];
            }
        }

        return [$deductions, $restores];
    }

    private function buildOptionSchema(object $order): array
    {
        $template = $order->orderTemplate;
        if (! $template) {
            return [];
        }

        $selected = $order->selected_options ?? [];

        return $template->options
            ->sortBy('position')
            ->map(function ($option) use ($selected): array {
                $optionKey = (string) $option->id;
                $selectedTypeId = $selected[$optionKey] ?? $selected[$option->id] ?? null;

                return [
                    'id' => (int) $option->id,
                    'label' => (string) $option->label,
                    'position' => (int) $option->position,
                    'selected_type_id' => $selectedTypeId !== null ? (int) $selectedTypeId : null,
                    'types' => $option->optionTypes
                        ->where('is_available', true)
                        ->sortBy('position')
                        ->map(fn ($type): array => [
                            'id' => (int) $type->id,
                            'type_name' => (string) $type->type_name,
                            'position' => (int) $type->position,
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();
    }

    private function buildRushFeeOptions(): array
    {
        return RushFee::query()
            ->with([
                'timeframes' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
            ])
            ->orderBy('label')
            ->get()
            ->map(function (RushFee $rushFee): array {
                return [
                    'id' => (int) $rushFee->id,
                    'label' => (string) $rushFee->label,
                    'timeframes' => $rushFee->timeframes
                        ->map(fn ($timeframe): array => [
                            'id' => (int) $timeframe->id,
                            'label' => (string) $timeframe->label,
                            'percentage' => (float) $timeframe->percentage,
                            'sort_order' => (int) $timeframe->sort_order,
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();
    }

    private function normalizeGeneralDriveLink(Request $request): void
    {
        if (! $request->has('general_drive_link')) {
            return;
        }

        $request->merge([
            'general_drive_link' => GoogleDriveUrl::normalize($request->input('general_drive_link')),
        ]);
    }
}
