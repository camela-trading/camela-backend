<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Setting extends Model
{
    protected $fillable = [

        'user_id',

        'dark_mode',

        'language',

        'order_updates',

        'promotions',

        'profile_visible',

    ];

    protected $casts = [

        'dark_mode' => 'boolean',

        'order_updates' => 'boolean',

        'promotions' => 'boolean',

        'profile_visible' => 'boolean',

    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}