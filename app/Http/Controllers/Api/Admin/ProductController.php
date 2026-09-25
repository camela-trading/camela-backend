<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\BulkDuplicateProductsRequest;
use App\Http\Requests\Product\BulkUpdateProductStatusRequest;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\Product\ProductService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        private ProductService $productService
    ) {}

    public function index(Request $request)
    {
        return ProductResource::collection(
            $this->productService->catalog($request->all(), true)
        );
    }

    public function store(StoreProductRequest $request)
    {
        $product = $this->productService->create(
            $request->validated()
        );

        return new ProductResource(
            $product->load('images')
        );
    }

    public function show(Product $product)
    {
        return new ProductResource(

            $product->load([
                'category',
                'images'
            ])

        );
    }

    public function update(
        UpdateProductRequest $request,
        Product $product
    )
    {
        $product = $this->productService
            ->update(
                $product,
                $request->validated()
            );

        return new ProductResource(

            $product->load('images')

        );
    }

    public function destroy(Product $product)
    {
        $this->productService
            ->delete($product);

        return response()->json([

            'success'=>true,

            'message'=>'Product deleted.'

        ]);
    }

    public function duplicate(Product $product)
    {
        $duplicate = $this->productService->duplicate($product);

        return new ProductResource($duplicate);
    }

    public function bulkDuplicate(BulkDuplicateProductsRequest $request)
    {
        return ProductResource::collection(
            $this->productService->duplicateByIds($request->validated('product_ids'))
        );
    }

    public function bulkStatus(BulkUpdateProductStatusRequest $request)
    {
        $data = $request->validated();

        return ProductResource::collection(
            $this->productService->updateStatusByIds($data['product_ids'], $data['status'])
        );
    }

    public function category($category)
    {
        return ProductResource::collection(

            $this->productService

                ->byCategory($category)

        );
    }
}
