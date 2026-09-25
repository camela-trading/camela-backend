<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Address;
use App\Models\Setting;
use App\Notifications\VerifyEmailNotification;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'role_id',
        'is_active',
        'last_login_at',
        'name',
        'username',
        'email',
        'phone',
        'avatar',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'verification_reminder_sent_at' => 'datetime',
            'cart_activity_at' => 'datetime',
            'abandoned_cart_reminder_activity_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function inventoryTransactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function getDisplayIdAttribute()
    {
        if ($this->role?->name === 'ADMIN') {

            $number = User::whereHas('role', function ($q) {
                $q->where('name', 'ADMIN');
            })
            ->where('id', '<=', $this->id)
            ->count();

            return 'A' . str_pad($number, 3, '0', STR_PAD_LEFT);
        }

        $number = User::whereHas('role', function ($q) {
            $q->where('name', 'CUSTOMER');
        })
        ->where('id', '<=', $this->id)
        ->count();

        return 'C' . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    public function setting()
    {
        return $this->hasOne(Setting::class);
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification);
    }

    public function hasVerifiedEmail(): bool
    {
        return ! is_null($this->email_verified_at);
    }
}
