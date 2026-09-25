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
}
