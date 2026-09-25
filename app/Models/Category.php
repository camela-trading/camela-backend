<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [

        'name',

        'slug',

        'description',

        'image',

        'banner',

        'images',

        'landing_page',

        'sort_order',

        'seo_title',

        'seo_description',

        'is_active',

    ];

    protected $casts = [
        'images' => 'array',
        'is_active' => 'boolean',
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
