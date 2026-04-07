<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property string $status
 * @property string $payment_status
 * @property string|null $cancellation_reason
 * @property string|null $general_drive_link
 * @property string|null $payment_method
 * @property string|null $payment_reference_number
 * @property string|null $payment_proof_path
 * @property string|null $payment_confirmation_note
 * @property \\Illuminate\\Support\\Carbon|null $payment_submitted_at
 * @property \\Illuminate\\Support\\Carbon|null $payment_confirmed_at
 * @property int|null $payment_confirmed_by
 * @property string|null $subtotal_price
 * @property string|null $discount_total
 * @property string|null $rush_fee_total
 * @property string|null $layout_fee_total
 * @property string|null $total_price
 * @property array<int, array<string, int|string>>|null $inventory_material_requirements
 * @property \Illuminate\Support\Carbon|null $inventory_deducted_at
 * @property \Illuminate\Support\Carbon|null $inventory_restored_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read string $status_label
 * @property-read User $user
 * @property-read \Illuminate\Database\Eloquent\Collection|CustomerOrder[] $orders
 */
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

    public const PAYMENT_STATUSES = [
        'awaiting_payment',
        'waiting_payment_confirmation',
        'payment_received',
        'payment_cancelled',
    ];

    public const CANCELLATION_REASONS = [
        'owner_declined',
        'customer_cancelled',
    ];

    public const PAYMENT_METHODS = [
        'gcash',
        'bpi',
        'paymaya',
    ];

    protected $fillable = [
        'user_id',
        'status',
        'payment_status',
        'cancellation_reason',
        'general_drive_link',
        'payment_method',
        'payment_reference_number',
        'payment_proof_path',
        'payment_submitted_at',
        'payment_confirmed_at',
        'payment_confirmed_by',
        'payment_confirmation_note',
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
            'payment_submitted_at' => 'datetime',
            'payment_confirmed_at' => 'datetime',
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

        if ($this->status === 'approved' && $nextStatus === 'preparing') {
            return $this->payment_status === 'payment_received';
        }

        return in_array($nextStatus, static::allowedTransitions()[$this->status] ?? [], true);
    }

    public function canCustomerCancel(): bool
    {
        return $this->status === 'waiting';
    }

    public function canOwnerDecline(): bool
    {
        return in_array($this->status, ['waiting', 'approved'], true);
    }

    public function canSubmitPaymentProof(): bool
    {
        return $this->status === 'approved'
            && $this->payment_status === 'awaiting_payment';
    }

    public function canConfirmPayment(): bool
    {
        return $this->status === 'approved'
            && $this->payment_status === 'waiting_payment_confirmation';
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
            'waiting' => 'Waiting for Order Approval',
            'approved' => 'Order Approved',
            'preparing' => 'Preparing Order',
            'ready' => 'Ready for Shipping',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            default => ucfirst($this->status),
        };
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        return match ($this->payment_status) {
            'awaiting_payment' => 'Awaiting Payment',
            'waiting_payment_confirmation' => 'Waiting for Payment Confirmation',
            'payment_received' => 'Payment Received',
            'payment_cancelled' => 'Payment Cancelled',
            default => ucfirst(str_replace('_', ' ', (string) $this->payment_status)),
        };
    }
}
