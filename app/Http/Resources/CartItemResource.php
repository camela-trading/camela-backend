<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    public function toArray($request): array
    {
        $price = (float) $this->product->price;

        return [

            'id' => $this->id,

            'quantity' => $this->quantity,

            'price' => $price,

            'subtotal' => $price * $this->quantity,

            'product' => new ProductResource(

                $this->whenLoaded('product')

            ),

            'created_at' => $this->created_at,

        ];
    }
}