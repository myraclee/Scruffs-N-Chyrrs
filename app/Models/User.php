<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string $contact_number
 * @property string $password
 * @property string $user_type
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'contact_number',
        'password',
        'user_type',
        'login_attempts',
        'lockout_until',
        'is_locked',
        'must_reset_password',
        'password_reset_completed_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'lockout_until' => 'datetime',
            'is_locked' => 'boolean',
            'must_reset_password' => 'boolean',
            'password_reset_completed_at' => 'datetime',
        ];
    }

    /**
     * Check if the user is an owner.
     */
    public function isOwner(): bool
    {
        return $this->user_type === 'owner';
    }

    /**
     * Get the user's persistent cart.
     */
    public function cart(): HasOne
    {
        return $this->hasOne(CustomerCart::class);
    }

    /**
     * Get grouped orders placed by the user.
     */
    public function customerOrderGroups(): HasMany
    {
        return $this->hasMany(CustomerOrderGroup::class);
    }

    /**
     * Get item-level customer order records for the user.
     */
    public function customerOrders(): HasMany
    {
        return $this->hasMany(CustomerOrder::class);
    }
}
