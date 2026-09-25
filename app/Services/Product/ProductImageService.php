<?php

namespace App\Services\Product;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductImageService
{
    

    public function getImages(Product $product)
    {
        return $product
            ->images()
            ->orderBy('sort_order')
            ->get();
    }

    public function upload(
        Product $product,
        array $images
    )
    {
        return DB::transaction(function () use ($product, $images) {

            foreach ($images as $index => $image) {

                $path = $image->store(
                    'products',
                    'public'
                );

                $imageModel = $product->images()->create([

                    'image_path' => $path,

                    'sort_order' => $product->images()->count() + $index,

                    'is_primary' => $product->images()->count() === 0,

                ]);
                
            }

            return $product
                ->images()
                ->orderBy('sort_order')
                ->get();
        });
    }

    public function delete(ProductImage $image): void
    {
        DB::transaction(function () use ($image) {

            $product = $image->product;

            $deletedWasPrimary = $image->is_primary;

            if (

                Storage::disk('public')
                    ->exists($image->image_path)

            ) {

                Storage::disk('public')
                    ->delete($image->image_path);

            }

            $image->delete();

            if ($deletedWasPrimary) {

                $newPrimary = $product
                    ->images()
                    ->orderBy('sort_order')
                    ->first();

                if ($newPrimary) {

                    $newPrimary->update([

                        'is_primary' => true

                    ]);

                } else {

                }

            }

        });
    }

    public function setPrimary(ProductImage $image): void
    {
        DB::transaction(function () use ($image) {

            ProductImage::where(

                'product_id',
                $image->product_id

            )->update([

                'is_primary' => false

            ]);

            $image->update([

                'is_primary' => true

            ]);

        });
    }

    public function reorder(
        Product $product,
        array $images
    ): void
    {
        foreach ($images as $order => $id) {

            ProductImage::where(

                'product_id',
                $product->id

            )

            ->where(

                'id',
                $id

            )

            ->update([

                'sort_order' => $order

            ]);
        }
    }
}
