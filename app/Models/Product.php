<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string $cover_image_path
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'description',
        'cover_image_path',
    ];

    /**
     * Get the price images for the product.
     */
    public function priceImages(): HasMany
    {
        return $this->hasMany(ProductPriceImage::class)->orderBy('sort_order');
    }

    /**
     * Get the materials associated with this product.
     */
    public function materials(): BelongsToMany
    {
        return $this->belongsToMany(Material::class, 'product_material')
            ->withPivot('quantity')
            ->withTimestamps();
    }
}
