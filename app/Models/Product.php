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

        'price_without_gst',
'price_with_gst',
'price_basis',

'nonmember_price_without_gst',
'nonmember_price_with_gst',
'nonmember_price_basis',

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

    // public function lineTotal(int $qty): float
    // {
    //     if (!$this->promo_buy || !$this->promo_free) {
    //         return $this->price * $qty;
    //     }

    //     $free = intdiv($qty, $this->promo_buy + $this->promo_free) * $this->promo_free;

    //     return $this->price * ($qty - $free);
    // }
    public function nonMemberPrice(): float
    {
        $price = $this->nonmember_price_basis === 'with_gst'
            ? $this->nonmember_price_with_gst
            : $this->nonmember_price_without_gst;

        // falls back to the pre-discount price if never set
        return (float) ($price ?? $this->compare_price ?? $this->price);
    }

    public function lineTotal(int $qty, bool $isMember = true): float
    {
        if (!$isMember) {
            return $this->nonMemberPrice() * $qty; // no promo for non-members
        }

        if (!$this->promo_buy || !$this->promo_free) {
            return $this->price * $qty;
        }

        $free = intdiv($qty, $this->promo_buy + $this->promo_free) * $this->promo_free;

        return $this->price * ($qty - $free);
    }
}
