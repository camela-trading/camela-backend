<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class UploadProductImagesRequest extends FormRequest
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

                'max:10',

            ],

            'images.*' => [

                'required',

                'image',

                'mimes:jpg,jpeg,png,webp',

                'max:5120',

            ],

        ];
    }
}