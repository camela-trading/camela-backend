<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\Product\ProductService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(

        private ProductService $productService

    ) {}

    /*
    |--------------------------------------------------------------------------
    | Product Catalog
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $products = $this->productService->catalog(

            $request->only([

                'search',

                'category',

                'featured',

                'sort'

            ])

        );

        return ProductResource::collection($products);
    }

    /*
    |--------------------------------------------------------------------------
    | Product Detail
    |--------------------------------------------------------------------------
    */

    public function show($id)
    {
        $product = $this->productService->find($id);

        return new ProductResource($product);
    }

    /*
    |--------------------------------------------------------------------------
    | Search
    |--------------------------------------------------------------------------
    */

    public function search(Request $request)
    {
        $products = $this->productService
            ->search(

                $request->q

            );

        return ProductResource::collection(

            $products

        );
    }

    /*
    |--------------------------------------------------------------------------
    | Related Products
    |--------------------------------------------------------------------------
    */

    public function related($id)
    {
        $product = Product::findOrFail($id);

        return ProductResource::collection(

            $this->productService

                ->related($product)

        );
    }

    public function category($category)
    {
        return ProductResource::collection(

            $this->productService->byCategory($category)

        );
    }
}
