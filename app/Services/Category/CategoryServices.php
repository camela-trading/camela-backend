<?php

namespace App\Services\Category;

use App\Models\Category;

class CategoryService
{
    public function all()
    {
        return Category::orderBy('name')->get();
    }
}