<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

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
        'slug',
        'description',
        'cover_image_path',
    ];

    /**
     * Boot the model and set up observers/event listeners.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->slug) {
                $model->slug = Str::slug($model->name);
            }
        });

        static::updating(function ($model) {
            if ($model->isDirty('name') && !$model->isDirty('slug')) {
                $model->slug = Str::slug($model->name);
            }
        });
    }

    /**
     * Get the route key for implicit route binding.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Get the price images for the product.
     */
    public function priceImages(): HasMany
    {
        return $this->hasMany(ProductPriceImage::class)->orderBy('sort_order');
    }

    /**
     * Get the note images for the product.
     */
    public function noteImages(): HasMany
    {
        return $this->hasMany(ProductNoteImage::class)->orderBy('sort_order');
    }

    /**
     * Get the order template for this product.
     */
    public function orderTemplate(): HasOne
    {
        return $this->hasOne(OrderTemplate::class);
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

    /**
     * Get option-aware material consumption mappings for this product.
     */
    public function materialConsumptions(): HasMany
    {
        return $this->hasMany(MaterialConsumption::class);
    }
}
