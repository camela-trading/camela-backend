<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'order_status' => [

                'required',

                Rule::in([

                    'PENDING',

                    'PROCESSING',

                    'SHIPPED',

                    'DELIVERED',

                    'CANCELLED'

                ])

            ]

        ];
    }
}