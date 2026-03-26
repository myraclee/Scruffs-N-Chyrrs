<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

/**
 * @property int $id
 * @property string $label
 * @property string $min_price
 * @property string $max_price
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class RushFee extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'rush_fees';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'label',
        'min_price',
        'max_price',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'min_price' => 'decimal:2',
        'max_price' => 'decimal:2',
    ];

    /**
     * Get all timeframes for this rush fee.
     *
     * @return HasMany
     */
    public function timeframes(): HasMany
    {
        return $this->hasMany(RushFeeTimeframe::class, 'rush_fee_id');
    }

    /**
     * Scope to get rush fees ordered by price range.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('min_price', 'asc');
    }
}
