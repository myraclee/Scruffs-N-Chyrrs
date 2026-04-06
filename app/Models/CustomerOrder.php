<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $customer_order_group_id
 * @property int $user_id
 * @property int $product_id
 * @property int $order_template_id
 * @property int|null $rush_fee_id
 * @property array<string, int|string>|null $selected_options
 * @property int $quantity
 * @property string|null $special_instructions
 * @property string $base_price
 * @property string $discount_amount
 * @property string $rush_fee_amount
 * @property string $layout_fee_amount
 * @property string $total_price
 * @property string $status
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read string $status_label
 * @property-read array<int, array<string, string>> $formatted_options
 * @property-read User $user
 * @property-read CustomerOrderGroup $orderGroup
 * @property-read Product $product
 * @property-read OrderTemplate $orderTemplate
 * @property-read RushFee $rushFee
 */
class CustomerOrder extends Model
{
    /** @use HasFactory<\Database\Factories\CustomerOrderFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'customer_order_group_id',
        'user_id',
        'product_id',
        'order_template_id',
        'rush_fee_id',
        'selected_options',
        'quantity',
        'special_instructions',
        'base_price',
        'discount_amount',
        'rush_fee_amount',
        'layout_fee_amount',
        'total_price',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'selected_options' => 'array',
            'base_price' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'rush_fee_amount' => 'decimal:2',
            'layout_fee_amount' => 'decimal:2',
            'total_price' => 'decimal:2',
        ];
    }

    /**
     * Get the user that placed this order.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the grouped checkout record this item belongs to.
     */
    public function orderGroup(): BelongsTo
    {
        return $this->belongsTo(CustomerOrderGroup::class, 'customer_order_group_id');
    }

    /**
     * Get the product being ordered.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the order template configuration used.
     */
    public function orderTemplate(): BelongsTo
    {
        return $this->belongsTo(OrderTemplate::class)->withTrashed();
    }

    /**
     * Get the rush fee selected (if any).
     */
    public function rushFee(): BelongsTo
    {
        return $this->belongsTo(RushFee::class)->withDefault();
    }

    /**
     * Get human-readable status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'waiting' => 'Waiting for Approval',
            'approved' => 'Order Approved',
            'preparing' => 'Preparing Order',
            'ready' => 'Ready for Shipping',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get formatted selected options for display.
     * Returns a list of objects for API/UI consistency.
     */
    public function getFormattedOptionsAttribute(): array
    {
        if (! $this->orderTemplate || ! $this->selected_options) {
            return [];
        }

        $formatted = [];

        foreach ($this->orderTemplate->options as $option) {
            $optionKey = (string) $option->id;

            if (isset($this->selected_options[$optionKey]) || isset($this->selected_options[$option->id])) {
                $selectedValue = $this->selected_options[$optionKey] ?? $this->selected_options[$option->id];

                // Find the option type label
                if (is_numeric($selectedValue)) {
                    $optionType = $option->optionTypes->first(fn ($ot) => (int) $ot->id === (int) $selectedValue);
                    $formatted[] = [
                        'option_label' => $option->label,
                        'selected_value' => $optionType?->type_name ?? (string) $selectedValue,
                    ];
                } else {
                    $formatted[] = [
                        'option_label' => $option->label,
                        'selected_value' => (string) $selectedValue,
                    ];
                }
            }
        }

        return $formatted;
    }
}
