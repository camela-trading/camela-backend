<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [

            'id' => $this->id,

            'name' => $this->name,

            'slug' => $this->slug,

            'image' => $this->image,

            'banner' => $this->banner,

            'images' => $this->images ?? [],

            'landing_page' => $this->landing_page,

            'description' => $this->description,

            'sort_order' => $this->sort_order,

            'is_active' => $this->is_active,

            'seo_title' => $this->seo_title,

            'seo_description' => $this->seo_description,

        ];
    }
}
