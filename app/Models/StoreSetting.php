<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreSetting extends Model
{
    protected $fillable = [

        'store_name',

        'tagline',

        'support_email',

        'phone',

        'address',

        'logo',

        'standard_shipping',

        'express_shipping',

        'overnight_shipping',

        'free_shipping_threshold',

        'tax_rate',

        'default_low_stock_threshold',

        'maintenance_mode',

        'notify_new_order',

        'notify_low_stock',

        'notify_new_customer',

        'notify_order_delivered',

    ];

    protected $casts = [

        'maintenance_mode'=>'boolean',

        'notify_new_order'=>'boolean',

        'notify_low_stock'=>'boolean',

        'notify_new_customer'=>'boolean',

        'notify_order_delivered'=>'boolean',

    ];
}
