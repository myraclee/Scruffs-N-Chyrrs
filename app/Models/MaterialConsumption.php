<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $material_id
 * @property int $product_id
 * @property int|null $order_template_option_type_id
 * @property int $quantity
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read Material $material
 * @property-read Product $product
 * @property-read OrderTemplateOptionType|null $optionType
 */
class MaterialConsumption extends Model
{
    /** @use HasFactory<\Database\Factories\MaterialConsumptionFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'material_id',
        'product_id',
        'order_template_option_type_id',
        'quantity',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'material_id' => 'integer',
            'product_id' => 'integer',
            'order_template_option_type_id' => 'integer',
            'quantity' => 'integer',
        ];
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function optionType(): BelongsTo
    {
        return $this->belongsTo(OrderTemplateOptionType::class, 'order_template_option_type_id');
    }
}
