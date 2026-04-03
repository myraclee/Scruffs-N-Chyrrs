<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        // 1. Wrap everything in a transaction
        return DB::transaction(function () use ($request) {
            
            // 2. Create the Main Envelope (Order)
            $order = Order::create([
                'user_id' => Auth::id(), // <--- Changed this line!
                'general_gdrive_link' => $request->general_gdrive_link,
                'rush_fee' => $request->rush_fee ?? 0,
                'status' => 'pending'
            ]);

            $runningGrandTotal = $order->rush_fee;

            // 3. Loop through every row they added to the modal
            if ($request->has('items')) {
                foreach ($request->items as $item) {
                    
                    $price = $item['price'];
                    $quantity = $item['quantity'];
                    $layoutFee = 0;
                    $discount = 0;

                    // --- THE EXCEL BUSINESS RULES --- //

                    if ($item['category'] == 'Stickers') {
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
                        'type' => $item['type'],
                        'lamination' => $item['lamination'],
                        'design_name_link' => $item['design_name_link'],
                        'quantity' => $quantity,
                        'layout_fee' => $layoutFee,
                        'item_total' => $itemTotal
                    ]);
                }
            }

            // 4. Update the envelope with the final calculated Grand Total
            $order->update(['grand_total' => $runningGrandTotal]);

            // 5. Send a success message back to the frontend
            return response()->json([
                'success' => true,
                'message' => 'Order placed successfully!',
                'order_id' => $order->id
            ]);
        });
    }
}