<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [

        'category_id',

        'title',

        'slug',

        'sku',

        'short_description',

        'description',

        'price',

        'compare_price',

        'cost_price',

        'stock',

        'low_stock_alert',

        'weight',

        'status',

        'featured',

        'seo_title',

        'seo_description',

        'description_ms', // added by Darah | translation to malay

        'description_zh', // added by Darah | translation to chinese
      
        'promo_buy', // added by Darah | promo buy
      
        'promo_free', // added by Darah | promo free
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function inventoryTransactions()
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    public function lineTotal(int $qty): float
    {
        if (!$this->promo_buy || !$this->promo_free) {
            return $this->price * $qty;
        }

        $free = intdiv($qty, $this->promo_buy + $this->promo_free) * $this->promo_free;

        return $this->price * ($qty - $free);
    }
}
