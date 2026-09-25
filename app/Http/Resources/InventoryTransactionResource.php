<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryTransactionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [

            'id'=>$this->id,

            'type'=>$this->type,

            'quantity'=>$this->quantity,

            'stock_before'=>$this->stock_before,

            'stock_after'=>$this->stock_after,

            'remarks'=>$this->remarks,

            'product'=>$this->whenLoaded('product'),

            'user'=>$this->whenLoaded('user'),

            'created_at'=>$this->created_at,

        ];
    }
}