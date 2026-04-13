<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CustomerOrderGroup;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        // 1. Validate incoming data from Aaron's frontend so it doesn't break the DB
        $request->validate([
            'general_gdrive_link' => 'nullable|url',
            'rush_fee' => 'nullable|numeric',
            'items' => 'required|array',
            'items.*.category' => 'required|string',
            'items.*.price' => 'required|numeric',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        // 2. Wrap everything in a transaction
        return DB::transaction(function () use ($request) {
            
            // 3. Create the Main Envelope (Order)
            // 3. Create the Main Envelope (Order)
            $order = Order::create([
                'user_id' => Auth::id(), 
                'general_gdrive_link' => $request->general_gdrive_link,
                'rush_fee' => $request->rush_fee ?? 0,
                // 👉 Matches SA Flow Step 1 exactly:
                'status' => 'Waiting for Order Approval',
                'payment_status' => 'Awaiting Payment' 
            ]);

            $runningGrandTotal = $order->rush_fee;

            // 4. Loop through every row they added to the cart
            if ($request->has('items')) {
                foreach ($request->items as $item) {
                    
                    $price = $item['price'];
                    $quantity = $item['quantity'];
                    $layoutFee = 0;
                    $discount = 0;

                    // --- THE EXCEL BUSINESS RULES --- //

                    if ($item['category'] == 'Stickers') {
                        // 👉 CHRISTA'S LOGIC: This already perfectly adds 1 flat fee per cart item!
                        if (isset($item['needs_layout']) && $item['needs_layout']) {
                            $layoutFee = 35.00;
                        }
                        if ($quantity >= 7) {
                            $discount = 5.00 * $quantity; 
                        }
                    }

                    if ($item['category'] == 'Button Pins') {
                        if ($quantity >= 10) {
                            $discount = 3.00 * $quantity; 
                        }
                    }

                    // Calculate this specific row's total
                    $itemTotal = ($price * $quantity) + $layoutFee - $discount;
                    
                    // Add it to the envelope's grand total
                    $runningGrandTotal += $itemTotal;

                    // Save the row to the database
                    $order->items()->create([
                        'category' => $item['category'],
                        'type' => $item['type'] ?? null,
                        'lamination' => $item['lamination'] ?? null,
                        'design_name_link' => $item['design_name_link'] ?? null,
                        'quantity' => $quantity,
                        'layout_fee' => $layoutFee,
                        'item_total' => $itemTotal
                    ]);
                }
            }

            // 5. Update the envelope with the final calculated Grand Total
            $order->update(['grand_total' => $runningGrandTotal]);

            // 6. Send a success message back to the frontend
            return response()->json([
                'success' => true,
                'message' => 'Order submitted and is waiting for admin approval!',
                'order_id' => $order->id
            ]);
        });
    }

   public function submitPayment(Request $request, $id)
    {
        $path = null;

        try {
            $order = CustomerOrderGroup::where('id', $id)
                ->where('user_id', Auth::id())
                ->first();

            if (!$order) {
                return response()->json(['success' => false, 'message' => "Order Group #{$id} could not be found..."], 404);
            }

            if (! $order->canSubmitPaymentProof()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment proof can only be submitted for approved orders awaiting payment.',
                ], 422);
            }

            $request->validate([
                'payment_proof' => 'required|image|max:5120', 
            ]);

            $path = $request->file('payment_proof')->store('payment_proofs', 'public');

            $order->update([
                'payment_status' => 'waiting_payment_confirmation',
                'payment_proof_path' => $path,
                'payment_submitted_at' => now(),
                'payment_confirmed_at' => null,
                'payment_confirmed_by' => null,
                'payment_confirmation_note' => null,
            ]);

            return response()->json(['success' => true, 'message' => 'Payment submitted successfully!']);

        } catch (\Throwable $e) {
            if ($path !== null) {
                Storage::disk('public')->delete($path);
            }

            return response()->json(['success' => false, 'message' => 'PHP Crash: ' . $e->getMessage()], 500);
        }
    }
    public function updatePaymentStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'payment_status' => 'required|in:'.implode(',', CustomerOrderGroup::PAYMENT_STATUSES),
        ]);

        $order = CustomerOrderGroup::find($id);
        
        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => "Order Group #{$id} not found."
            ], 404);
        }

        $order->update([
            'payment_status' => $validated['payment_status'],
        ]);

        return response()->json(['success' => true]);
    }
}