<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProductImageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $imagePath = ltrim((string) $this->image_path, '/');

        if (str_starts_with($imagePath, 'storage/')) {
            $imagePath = substr($imagePath, 8);
        }

        return [

            'id' => $this->id,

            'image_url' => Storage::disk('public')->url($imagePath),

            'image_path' => $this->image_path,

            'alt_text' => $this->alt_text,

            'sort_order' => $this->sort_order,

            'is_primary' => $this->is_primary,

            'created_at' => $this->created_at
                ? $this->created_at
                    ->timezone('Asia/Singapore')
                    ->format('Y-m-d H:i:s')
                : null,

            'updated_at' => $this->updated_at
                ? $this->updated_at
                    ->timezone('Asia/Singapore')
                    ->format('Y-m-d H:i:s')
                : null,

        ];
    }
}
