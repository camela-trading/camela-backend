<?php

namespace App\Http\Requests\Product;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
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
                'sometimes',
                'string',
                'max:255'
            ],

            'sku'=>[
                'sometimes',
                'string',
                'max:255',
                'unique:products,sku,' . $this->route('product')?->id,
            ],

            'price'=>[
                'sometimes',
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
                'sometimes',
                'integer',
                'min:0'
            ],

            'short_description'=>[
                'nullable',
                'string'
            ],

            'description'=>[
                'sometimes',
                'string'
            ],

            'status' => [
                'required',
                'in:ACTIVE,INACTIVE',
            ],

            'featured'=>[
                'boolean'
            ],

            'is_active'=>[
                'boolean'
            ],

        ];
    }
}
