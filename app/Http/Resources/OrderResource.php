<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray($request): array
    {
        return [

            'id' => $this->id,

            'order_number' => $this->order_number,

            'subtotal' => (float) $this->subtotal,

            'shipping_fee' => (float) $this->shipping_fee,

            'discount' => (float) $this->discount,

            'tax' => (float) $this->tax,

            'grand_total' => (float) $this->grand_total,

            'payment_status' => $this->payment_status,

            'order_status' => $this->order_status,

            'payment_method' => $this->payment_method,

            'payment_request_id' => $this->payment_request_id,

            'hitpay_reference' => $this->hitpay_reference,

            'transaction_reference' => $this->transaction_reference,

            'gateway_response' => $this->gateway_response,

            'callback_response' => $this->callback_response,

            'payment_reference' => $this->payment_reference,

            'paid_at' => $this->paid_at,

            'customer' => new UserResource(

                $this->whenLoaded('user')

            ),

            'items' => OrderItemResource::collection(

                $this->whenLoaded('items')

            ),

            'shipping_address' => new AddressResource(
                $this->whenLoaded('shippingAddress')
            ),

            'billing_address' => new AddressResource(
                $this->whenLoaded('billingAddress')
            ),

            'created_at' => $this->created_at,

            'updated_at' => $this->updated_at,

        ];
    }
}
