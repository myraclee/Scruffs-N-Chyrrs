<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $rush_fee_id
 * @property string $label
 * @property string $percentage
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class RushFeeTimeframe extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'rush_fee_timeframes';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'rush_fee_id',
        'label',
        'percentage',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'percentage' => 'decimal:2',
    ];

    /**
     * Get the rush fee this timeframe belongs to.
     *
     * @return BelongsTo
     */
    public function rushFee(): BelongsTo
    {
        return $this->belongsTo(RushFee::class, 'rush_fee_id');
    }
}
