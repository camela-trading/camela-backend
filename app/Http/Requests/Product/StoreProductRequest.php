<?php

namespace App\Http\Requests\Product;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [

            'category_id'=>[
                'required',
                'exists:categories,id'
            ],

            'title'=>[
                'required',
                'string',
                'max:255'
            ],

            'sku'=>[
                'required',
                'string',
                'max:255',
                'unique:products,sku',
            ],

            'price'=>[
                'required',
                'numeric',
                'min:0'
            ],

            'compare_price'=>[
                'nullable',
                'numeric'
            ],

            'cost_price'=>[
                'nullable',
                'numeric',
                'min:0',
            ],

            'stock'=>[
                'required',
                'integer',
                'min:0'
            ],

            'low_stock_alert'=>[
                'nullable',
                'integer',
            ],

            'weight'=>[
                'nullable',
                'numeric',
            ],

            'status' => [
                'required',
                'in:ACTIVE,INACTIVE',
            ],

            'seo_title'=>[
                'nullable',
                'string',
                'max:255',
            ],

            'seo_description'=>[
                'nullable',
                'string',
            ],

            'short_description'=>[
                'nullable',
                'string'
            ],

            'description'=>[
                'required',
                'string'
            ],

            'featured'=>[
                'boolean'
            ],

            'is_active'=>[
                'boolean'
            ],

            'images' => [
                'nullable',
                'array',
                'max:10',
            ],

            'images.*' => [
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],

        ];
    }
}
