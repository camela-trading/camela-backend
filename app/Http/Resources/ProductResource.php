<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $resolveImageUrl = function (?string $path): ?string {
            if (!$path) {
                return null;
            }

            if (preg_match('#^https?://#i', $path)) {
                return $path;
            }

            $normalizedPath = ltrim($path, '/');

            if (str_starts_with($normalizedPath, 'storage/')) {
                $normalizedPath = substr($normalizedPath, 8);
            }

            return Storage::disk('public')->url($normalizedPath);
        };

        $images = $this->whenLoaded('images', function () use ($resolveImageUrl) {
            return $this->images
                ->sortBy('sort_order')
                ->map(function ($image) use ($resolveImageUrl) {
                    return $resolveImageUrl($image->image_path);
                })
                ->values();
        });

        $primaryImage = null;

        if ($this->relationLoaded('images')) {

            $primary = $this->images
                ->firstWhere('is_primary', true);

            if (!$primary) {
                $primary = $this->images->first();
            }

            if ($primary) {
                $primaryImage = $resolveImageUrl($primary->image_path);
            }
        }

        return [

            'id' => $this->id,

            'category_id' => $this->category_id,

            'title' => $this->title,

            'sku' => $this->sku,

            'price' => (float) $this->price,

            'compare_price' => $this->compare_price
                ? (float) $this->compare_price
                : null,

            'category' => optional($this->category)->name,

            'category_slug' => optional($this->category)->slug,

            'category_landing_page' => optional($this->category)->landing_page,

            'description' => $this->description,

            'short_description' => $this->short_description,

            'stock' => $this->stock,

            'low_stock' => $this->stock <= $this->low_stock_alert ? 1 : 0,

            'status' => $this->status,

            'featured' => (bool) $this->featured,

            /*
            |--------------------------------------------------------------------------
            | Frontend Compatibility
            |--------------------------------------------------------------------------
            */

            'image' => $primaryImage,

            'images' => $images,

        ];
    }
}
