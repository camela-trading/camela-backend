<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\UploadProductImagesRequest;
use App\Http\Requests\Product\SetPrimaryImageRequest;
use App\Http\Requests\Product\ReorderProductImagesRequest;
use App\Http\Resources\ProductImageResource;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\Product\ProductImageService;

class ProductImageController extends Controller
{
    public function __construct(
        private ProductImageService $productImageService
    ) {}

    /**
     * GET /products/{product}/images
     */
    public function index(Product $product)
    {
        return ProductImageResource::collection(

            $this->productImageService
                ->getImages($product)

        );
    }

    /**
     * POST /products/{product}/images
     */
    public function store(
        UploadProductImagesRequest $request,
        Product $product
    )
    {
        $images = $this->productImageService
            ->upload(
                $product,
                $request->file('images')
            );

        return ProductImageResource::collection($images);
    }

    /**
     * DELETE /products/images/{image}
     */
    public function destroy(ProductImage $image)
    {
        $this->productImageService
            ->delete($image);

        return response()->json([

            'success' => true,

            'message' => 'Image deleted successfully.'

        ]);
    }

    /**
     * PATCH /products/images/{image}/primary
     */
    public function setPrimary(
        SetPrimaryImageRequest $request,
        ProductImage $image
    )
    {
        $this->productImageService
            ->setPrimary($image);

        return response()->json([

            'success' => true,

            'message' => 'Primary image updated.'

        ]);
    }

    /**
     * PATCH /products/{product}/images/reorder
     */
    public function reorder(
        ReorderProductImagesRequest $request,
        Product $product
    )
    {
        $this->productImageService
            ->reorder(
                $product,
                $request->validated()['images']
            );

        return response()->json([

            'success' => true,

            'message' => 'Images reordered successfully.'

        ]);
    }
}