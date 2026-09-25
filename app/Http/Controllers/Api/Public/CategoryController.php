<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Models\Category;

class CategoryController extends Controller
{
    public function index()
    {
        return CategoryResource::collection(

            Category::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()

        );
    }

    public function show(Category $category)
    {
        abort_unless($category->is_active, 404);

        return response()->json([
            'data' => [
                'category' => new CategoryResource($category),
                'banner' => $category->banner,
                'description' => $category->description,
                'images' => $category->images ?? [],
                'seo' => [
                    'title' => $category->seo_title,
                    'description' => $category->seo_description,
                ],
                'products' => ProductResource::collection(
                    $category->products()->where('status', 'ACTIVE')->paginate()
                ),
            ],
        ]);
    }
}
