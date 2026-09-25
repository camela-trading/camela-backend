<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'type' => [

                'required',

                Rule::in([

                    'STOCK_IN',

                    'STOCK_OUT',

                    'ADJUSTMENT'

                ])

            ],

            'quantity' => [

                'required',

                'integer',

                'min:1'

            ],

            'remarks' => [

                'nullable',

                'string',

                'max:500'

            ]

        ];
    }
}