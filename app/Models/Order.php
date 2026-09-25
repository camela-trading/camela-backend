<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [

        'user_id',

        'order_number',

        'subtotal',

        'shipping_fee',

        'discount',

        'tax',

        'grand_total',

        'payment_status',

        'order_status',

        'payment_method',

        'payment_request_id',

        'hitpay_reference',

        'transaction_reference',

        'gateway_response',

        'callback_response',

        'shipping_address_id',

        'billing_address_id',

        'payment_reference',

        'paid_at',

    ];

    protected function casts(): array
    {
        return [

            'paid_at' => 'datetime',

            'subtotal' => 'decimal:2',

            'shipping_fee' => 'decimal:2',

            'discount' => 'decimal:2',

            'tax' => 'decimal:2',

            'grand_total' => 'decimal:2',

            'gateway_response' => 'array',

            'callback_response' => 'array',


        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function shippingAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'shipping_address_id');
    }

    public function billingAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'billing_address_id');
    }

    
}
