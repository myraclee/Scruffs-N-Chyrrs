<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerCartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_cart_id',
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
    ];

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

    public function cart(): BelongsTo
    {
        return $this->belongsTo(CustomerCart::class, 'customer_cart_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function orderTemplate(): BelongsTo
    {
        return $this->belongsTo(OrderTemplate::class);
    }

    public function rushFee(): BelongsTo
    {
        return $this->belongsTo(RushFee::class)->withDefault();
    }
}
