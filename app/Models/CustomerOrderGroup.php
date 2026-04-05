<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerOrderGroup extends Model
{
    use HasFactory;

    public const STATUSES = [
        'waiting',
        'approved',
        'preparing',
        'ready',
        'completed',
        'cancelled',
    ];

    protected $fillable = [
        'user_id',
        'status',
        'general_drive_link',
        'subtotal_price',
        'discount_total',
        'rush_fee_total',
        'layout_fee_total',
        'total_price',
        'inventory_material_requirements',
        'inventory_deducted_at',
        'inventory_restored_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal_price' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'rush_fee_total' => 'decimal:2',
            'layout_fee_total' => 'decimal:2',
            'total_price' => 'decimal:2',
            'inventory_material_requirements' => 'array',
            'inventory_deducted_at' => 'datetime',
            'inventory_restored_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(CustomerOrder::class);
    }

    public static function allowedTransitions(): array
    {
        return [
            'waiting' => ['approved', 'cancelled'],
            'approved' => ['preparing', 'cancelled'],
            'preparing' => ['ready', 'cancelled'],
            'ready' => ['completed', 'cancelled'],
            'completed' => [],
            'cancelled' => [],
        ];
    }

    public function canTransitionTo(string $nextStatus): bool
    {
        if ($this->status === $nextStatus) {
            return true;
        }

        return in_array($nextStatus, static::allowedTransitions()[$this->status] ?? [], true);
    }

    public function shouldRestockOnCancellation(string $nextStatus): bool
    {
        return $nextStatus === 'cancelled'
            && in_array($this->status, ['waiting', 'approved'], true)
            && $this->inventory_deducted_at !== null
            && $this->inventory_restored_at === null;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'waiting' => 'Waiting for Approval',
            'approved' => 'Order Approved',
            'preparing' => 'Preparing Order',
            'ready' => 'Ready for Shipping',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            default => ucfirst($this->status),
        };
    }
}
