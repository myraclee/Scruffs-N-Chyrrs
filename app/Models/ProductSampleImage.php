<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ProductSampleImage Model
 * Represents an image for a product sample
 * 
 * @property int $id
 * @property int $product_sample_id
 * @property string $image_path
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read ProductSample $sample
 */
class ProductSampleImage extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_sample_id',
        'image_path',
        'sort_order',
    ];

    /**
     * Get the product sample this image belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function sample(): BelongsTo
    {
        return $this->belongsTo(ProductSample::class);
    }
}
