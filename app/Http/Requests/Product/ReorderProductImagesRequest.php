<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class ReorderProductImagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'images' => [

                'required',

                'array',

            ],

            'images.*' => [

                'required',

                'integer',

                'exists:product_images,id',

            ],

        ];
    }
}