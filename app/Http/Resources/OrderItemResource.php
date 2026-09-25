<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray($request): array
    {
        return [

            'id' => $this->id,

            'quantity' => $this->quantity,

            'price' => (float) $this->price,

            'subtotal' => (float) $this->subtotal,

            'product' => new ProductResource(

                $this->whenLoaded('product')

            ),

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