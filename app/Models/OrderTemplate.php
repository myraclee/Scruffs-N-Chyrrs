<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $product_id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class OrderTemplate extends Model
{
    /** @use HasFactory<\Database\Factories\OrderTemplateFactory> */
    use HasFactory;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
    ];

    /**
     * Get the product associated with this order template.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the options for this order template.
     */
    public function options(): HasMany
    {
        return $this->hasMany(OrderTemplateOption::class)->orderBy('position');
    }

    /**
     * Get the pricings for this order template.
     */
    public function pricings(): HasMany
    {
        return $this->hasMany(OrderTemplatePricing::class);
    }

    /**
     * Get the discounts for this order template.
     */
    public function discounts(): HasMany
    {
        return $this->hasMany(OrderTemplateDiscount::class)->orderBy('position');
    }

    /**
     * Get the minimum order for this order template.
     */
    public function minOrder(): HasOne
    {
        return $this->hasOne(OrderTemplateMinOrder::class);
    }

    /**
     * Get the layout fee for this order template.
     */
    public function layoutFee(): HasOne
    {
        return $this->hasOne(OrderTemplateLayoutFee::class);
    }
}
