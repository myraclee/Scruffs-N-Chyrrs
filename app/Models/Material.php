<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property int $units
 * @property int $max_units
 * @property int $low_stock_threshold
 * @property string|null $description
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Material extends Model
{
    /** @use HasFactory<\Database\Factories\MaterialFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'units',
        'max_units',
        'low_stock_threshold',
        'description',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'units' => 'integer',
            'max_units' => 'integer',
            'low_stock_threshold' => 'integer',
        ];
    }

    /**
     * Get the products associated with this material.
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_material')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    /**
     * Get option-aware consumption rules linked to this material.
     */
    public function consumptions(): HasMany
    {
        return $this->hasMany(MaterialConsumption::class);
    }
}
