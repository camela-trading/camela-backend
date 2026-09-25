<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray($request): array
    {
        $subtotal = $this->sum(function ($item) {

            return (float) $item->product->price * $item->quantity;

        });

        return [

            'items' => CartItemResource::collection(

                $this

            ),

            'summary' => [

                'total_items' => (int) $this->sum('quantity'),

                'subtotal' => $subtotal,

                'grand_total' => $subtotal,

            ],

        ];
    }
}