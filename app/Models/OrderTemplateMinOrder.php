<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $order_template_id
 * @property int $min_quantity
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class OrderTemplateMinOrder extends Model
{
    /** @use HasFactory<\Database\Factories\OrderTemplateMinOrderFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'order_template_id',
        'min_quantity',
    ];

    /**
     * Get the order template that owns this minimum order record.
     */
    public function orderTemplate(): BelongsTo
    {
        return $this->belongsTo(OrderTemplate::class);
    }
}
