<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $name
 * @property int $units
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
}
