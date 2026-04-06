<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $order_template_option_id
 * @property string $type_name
 * @property bool $is_available
 * @property int $position
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class OrderTemplateOptionType extends Model
{
    /** @use HasFactory<\Database\Factories\OrderTemplateOptionTypeFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'order_template_option_id',
        'type_name',
        'is_available',
        'position',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_available' => 'boolean',
            'position' => 'integer',
        ];
    }

    /**
     * Get the option that owns this option type.
     */
    public function option(): BelongsTo
    {
        return $this->belongsTo(OrderTemplateOption::class, 'order_template_option_id');
    }

    /**
     * Get material consumption mappings that depend on this option type.
     */
    public function materialConsumptions(): HasMany
    {
        return $this->hasMany(MaterialConsumption::class, 'order_template_option_type_id');
    }
}
