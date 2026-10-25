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
        $user = $request->user('sanctum') ?? $request->user();
        $isAdmin = $user && $user->role_id === 1; 
        $isAdminRoute = $request->is('api/admin/*');
        $memberView = $isAdminRoute || ($user && $user->isMember());
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
        // added by darah
        $imagesDetailed = $this->whenLoaded('images', function () use ($resolveImageUrl) {
            return $this->images
                ->sortBy('sort_order')
                ->map(function ($image) use ($resolveImageUrl) {
                    return [
                        'id' => $image->id,
                        'url' => $resolveImageUrl($image->image_path),
                    ];
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

            // 'price' => (float) $this->price,

            // 'price_without_gst' => $this->price_without_gst !== null ? (float) $this->price_without_gst : null,
            // 'price_with_gst'    => $this->price_with_gst !== null ? (float) $this->price_with_gst : null,
            // 'price_basis'       => $this->price_basis,

            // 'compare_price' => $this->compare_price
            //     ? (float) $this->compare_price
            //     : null,

            // 'promo_buy' => $this->promo_buy, // added by Darah | promo buy
                
            // 'promo_free' => $this->promo_free, // added by Darah | promo free
'price' => (float) ($memberView ? $this->price : $this->nonMemberPrice()),

'price_without_gst' => $memberView ? $this->price_without_gst : $this->nonmember_price_without_gst,
'price_with_gst'    => $memberView ? $this->price_with_gst : $this->nonmember_price_with_gst,
'price_basis'       => $memberView ? $this->price_basis : $this->nonmember_price_basis,

'compare_price' => ($memberView && $this->compare_price) ? (float) $this->compare_price : null,
'promo_buy'  => $this->promo_buy,
'promo_free' => $this->promo_free,
'is_member'  => $memberView,
'member_price' => $memberView ? null : (float) $this->price,
'member_compare_price' => (!$memberView && $this->compare_price) ? (float) $this->compare_price : null,
// admin form needs the raw values
'nonmember_price_without_gst' => $isAdminRoute ? $this->nonmember_price_without_gst : null,
'nonmember_price_with_gst'    => $isAdminRoute ? $this->nonmember_price_with_gst : null,
'nonmember_price_basis'       => $isAdminRoute ? $this->nonmember_price_basis : null,

            'category' => optional($this->category)->name,

            'category_slug' => optional($this->category)->slug,

            'category_landing_page' => optional($this->category)->landing_page,

            'description' => $this->description,

            'description_ms' => $this->description_ms,  // added by Darah | translation to malay

            'description_zh' => $this->description_zh,  // added by Darah | translation to chinese

            'short_description' => $this->short_description,

            'stock' => $this->stock,

            'low_stock' => $this->stock <= $this->low_stock_alert ? 1 : 0,

            'status' => $this->status,

            'featured' => (bool) $this->featured,
            'weight'      => $this->weight !== null ? (float) $this->weight : null,
'weight_unit' => $this->weight_unit,
'length'      => $this->length !== null ? (float) $this->length : null,
'width'       => $this->width !== null ? (float) $this->width : null,
'height'      => $this->height !== null ? (float) $this->height : null,

            /*
            |--------------------------------------------------------------------------
            | Frontend Compatibility
            |--------------------------------------------------------------------------
            */

            'image' => $primaryImage,

            'images' => $images,

            'images_detailed' => $imagesDetailed,
            

        ];
    }
}
