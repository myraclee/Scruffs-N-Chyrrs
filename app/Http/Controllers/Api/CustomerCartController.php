<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerCart;
use App\Models\CustomerCartItem;
use App\Models\CustomerOrder;
use App\Models\CustomerOrderGroup;
use App\Models\OrderTemplate;
use App\Models\Product;
use App\Services\OrderPricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CustomerCartController extends Controller
{
    public function __construct(
        protected OrderPricingService $pricingService
    ) {
    }

    public function index(): JsonResponse
    {
        $cart = $this->getCartWithRelations();

        return response()->json([
            'success' => true,
            'data' => $this->transformCart($cart),
        ]);
    }

    public function storeItem(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'order_template_id' => 'required|exists:order_templates,id',
            'selected_options' => 'required|array|min:1',
            'quantity' => 'required|integer|min:1',
            'rush_fee_id' => 'nullable|exists:rush_fees,id',
            'special_instructions' => 'nullable|string|max:1000',
        ]);

        $product = Product::findOrFail($validated['product_id']);
        $template = OrderTemplate::with(['minOrder', 'options.optionTypes', 'pricings', 'discounts', 'layoutFee'])
            ->findOrFail($validated['order_template_id']);

        if (! $product->orderTemplate || (int) $product->orderTemplate->id !== (int) $template->id) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid order template for selected product.',
            ], 422);
        }

        $minOrder = $template->minOrder->min_quantity ?? 1;
        if ((int) $validated['quantity'] < $minOrder) {
            return response()->json([
                'success' => false,
                'message' => "Minimum order quantity is {$minOrder}.",
            ], 422);
        }

        $pricing = $this->pricingService->calculate(
            $template,
            $validated['selected_options'],
            (int) $validated['quantity'],
            $validated['rush_fee_id'] ?? null
        );

        if (!($pricing['success'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => $pricing['message'] ?? 'Unable to calculate cart item price.',
            ], 422);
        }

        $cart = CustomerCart::forUser((int) Auth::id());

        $normalizedOptions = $this->pricingService->normalizeSelectedOptions(
            $template,
            $validated['selected_options']
        );

        CustomerCartItem::create([
            'customer_cart_id' => $cart->id,
            'product_id' => $validated['product_id'],
            'order_template_id' => $validated['order_template_id'],
            'rush_fee_id' => $validated['rush_fee_id'] ?? null,
            'selected_options' => $normalizedOptions,
            'quantity' => $validated['quantity'],
            'special_instructions' => $validated['special_instructions'] ?? null,
            'base_price' => $pricing['base_price'],
            'discount_amount' => $pricing['discount_amount'],
            'rush_fee_amount' => $pricing['rush_fee_amount'],
            'layout_fee_amount' => $pricing['layout_fee_amount'],
            'total_price' => $pricing['total_price'],
        ]);

        $freshCart = $this->getCartWithRelations();

        return response()->json([
            'success' => true,
            'message' => 'Added to cart.',
            'data' => $this->transformCart($freshCart),
        ], 201);
    }

    public function updateItem(Request $request, CustomerCartItem $cartItem): JsonResponse
    {
        $this->assertItemOwnership($cartItem);

        $validated = $request->validate([
            'selected_options' => 'sometimes|array|min:1',
            'quantity' => 'sometimes|integer|min:1',
            'rush_fee_id' => 'nullable|exists:rush_fees,id',
            'special_instructions' => 'nullable|string|max:1000',
        ]);

        $template = $cartItem->orderTemplate()->with(['minOrder', 'options.optionTypes', 'pricings', 'discounts', 'layoutFee'])->firstOrFail();

        $updatedOptions = $validated['selected_options'] ?? $cartItem->selected_options;
        $updatedQuantity = (int) ($validated['quantity'] ?? $cartItem->quantity);
        $updatedRushFeeId = array_key_exists('rush_fee_id', $validated)
            ? $validated['rush_fee_id']
            : $cartItem->rush_fee_id;

        $normalizedOptions = $this->pricingService->normalizeSelectedOptions(
            $template,
            $updatedOptions
        );

        $minOrder = $template->minOrder->min_quantity ?? 1;
        if ($updatedQuantity < $minOrder) {
            return response()->json([
                'success' => false,
                'message' => "Minimum order quantity is {$minOrder}.",
            ], 422);
        }

        $pricing = $this->pricingService->calculate(
            $template,
            $normalizedOptions,
            $updatedQuantity,
            $updatedRushFeeId
        );

        if (!($pricing['success'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => $pricing['message'] ?? 'Unable to calculate cart item price.',
            ], 422);
        }

        $cartItem->update([
            'selected_options' => $normalizedOptions,
            'quantity' => $updatedQuantity,
            'rush_fee_id' => $updatedRushFeeId,
            'special_instructions' => $validated['special_instructions'] ?? $cartItem->special_instructions,
            'base_price' => $pricing['base_price'],
            'discount_amount' => $pricing['discount_amount'],
            'rush_fee_amount' => $pricing['rush_fee_amount'],
            'layout_fee_amount' => $pricing['layout_fee_amount'],
            'total_price' => $pricing['total_price'],
        ]);

        $freshCart = $this->getCartWithRelations();

        return response()->json([
            'success' => true,
            'message' => 'Cart item updated.',
            'data' => $this->transformCart($freshCart),
        ]);
    }

    public function destroyItem(CustomerCartItem $cartItem): JsonResponse
    {
        $this->assertItemOwnership($cartItem);

        $cartItem->delete();

        $freshCart = $this->getCartWithRelations();

        return response()->json([
            'success' => true,
            'message' => 'Cart item removed.',
            'data' => $this->transformCart($freshCart),
        ]);
    }

    public function checkout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'general_drive_link' => 'required|string|max:2048',
        ]);

        $cart = $this->getCartWithRelations();

        if ($cart->items->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Cart is empty. Add at least one item before checkout.',
            ], 422);
        }

        $group = DB::transaction(function () use ($cart, $validated) {
            $group = CustomerOrderGroup::create([
                'user_id' => Auth::id(),
                'status' => 'waiting',
                'general_drive_link' => $validated['general_drive_link'],
                'subtotal_price' => $cart->items->sum(fn ($item) => (float) $item->base_price),
                'discount_total' => $cart->items->sum(fn ($item) => (float) $item->discount_amount),
                'rush_fee_total' => $cart->items->sum(fn ($item) => (float) $item->rush_fee_amount),
                'layout_fee_total' => $cart->items->sum(fn ($item) => (float) $item->layout_fee_amount),
                'total_price' => $cart->items->sum(fn ($item) => (float) $item->total_price),
            ]);

            foreach ($cart->items as $cartItem) {
                CustomerOrder::create([
                    'customer_order_group_id' => $group->id,
                    'user_id' => Auth::id(),
                    'product_id' => $cartItem->product_id,
                    'order_template_id' => $cartItem->order_template_id,
                    'rush_fee_id' => $cartItem->rush_fee_id,
                    'selected_options' => $cartItem->selected_options,
                    'quantity' => $cartItem->quantity,
                    'special_instructions' => $cartItem->special_instructions,
                    'base_price' => $cartItem->base_price,
                    'discount_amount' => $cartItem->discount_amount,
                    'rush_fee_amount' => $cartItem->rush_fee_amount,
                    'layout_fee_amount' => $cartItem->layout_fee_amount,
                    'total_price' => $cartItem->total_price,
                    'status' => 'waiting',
                ]);
            }

            $cart->items()->delete();

            return $group;
        });

        return response()->json([
            'success' => true,
            'message' => 'Checkout complete. Your order is now waiting for approval.',
            'data' => [
                'order_group_id' => $group->id,
                'status' => $group->status,
                'total_price' => (float) $group->total_price,
            ],
        ]);
    }

    private function getCartWithRelations(): CustomerCart
    {
        return CustomerCart::forUser((int) Auth::id())
            ->load([
                'items.product:id,name,cover_image_path',
                'items.orderTemplate:id,product_id',
                'items.orderTemplate.options.optionTypes:id,order_template_option_id,type_name,is_available,position',
                'items.orderTemplate.options:id,order_template_id,label,position',
                'items.rushFee:id,label',
            ]);
    }

    private function assertItemOwnership(CustomerCartItem $cartItem): void
    {
        if (!Auth::check()) {
            abort(401, 'Authentication required');
        }

        $owned = $cartItem->cart()
            ->where('user_id', Auth::id())
            ->exists();

        if (!$owned) {
            abort(403, 'Unauthorized cart item access');
        }
    }

    private function transformCart(CustomerCart $cart): array
    {
        $items = $cart->items->map(function (CustomerCartItem $item) {
            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product?->name,
                'product_cover' => $item->product?->cover_image_path,
                'order_template_id' => $item->order_template_id,
                'rush_fee_id' => $item->rush_fee_id,
                'rush_fee_label' => $item->rushFee?->label,
                'selected_options' => $item->selected_options,
                'formatted_options' => $this->formatOptionsForItem($item),
                'quantity' => $item->quantity,
                'special_instructions' => $item->special_instructions,
                'base_price' => (float) $item->base_price,
                'discount_amount' => (float) $item->discount_amount,
                'rush_fee_amount' => (float) $item->rush_fee_amount,
                'layout_fee_amount' => (float) $item->layout_fee_amount,
                'total_price' => (float) $item->total_price,
                'created_at' => $item->created_at?->toISOString(),
            ];
        })->values();

        return [
            'id' => $cart->id,
            'status' => $cart->status,
            'items' => $items,
            'item_count' => $items->count(),
            'totals' => [
                'base_price' => round((float) $items->sum('base_price'), 2),
                'discount_amount' => round((float) $items->sum('discount_amount'), 2),
                'rush_fee_amount' => round((float) $items->sum('rush_fee_amount'), 2),
                'layout_fee_amount' => round((float) $items->sum('layout_fee_amount'), 2),
                'total_price' => round((float) $items->sum('total_price'), 2),
            ],
        ];
    }

    private function formatOptionsForItem(CustomerCartItem $item): array
    {
        $options = [];
        $template = $item->orderTemplate;

        if (!$template || !$item->selected_options) {
            return $options;
        }

        $templateOptions = $template->options->keyBy('id');

        foreach ($item->selected_options as $optionId => $typeId) {
            $option = $templateOptions->get((int) $optionId);
            if (!$option) {
                continue;
            }

            $type = $option->optionTypes->first(fn ($itemType) => (int) $itemType->id === (int) $typeId);
            $options[] = [
                'option_label' => $option->label,
                'selected_value' => $type?->type_name ?? (string) $typeId,
            ];
        }

        return $options;
    }
}
