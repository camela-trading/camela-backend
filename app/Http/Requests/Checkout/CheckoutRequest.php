<?php

namespace App\Http\Requests\Checkout;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            /*
            |--------------------------------------------------------------------------
            | Payment
            |--------------------------------------------------------------------------
            */

            'payment_method' => [

                'required',

                Rule::in([

                    'HITPAY',

                    'COD'

                ])

            ],

            /*
            |--------------------------------------------------------------------------
            | Future Fields
            |--------------------------------------------------------------------------
            */

            'shipping_address_id' => ['nullable', 'integer', Rule::exists('addresses', 'id')->where('user_id', $this->user()->id)],

            'billing_address_id' => ['nullable', 'integer', Rule::exists('addresses', 'id')->where('user_id', $this->user()->id)],

            'shipping_method' => ['nullable', Rule::in(['standard', 'express', 'overnight'])],

            'notes' => [

                'nullable',

                'string',

                'max:500'

            ]

        ];
    }
}
