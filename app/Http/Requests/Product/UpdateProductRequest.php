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

                        'price_basis' => [
                'sometimes',
                'in:without_gst,with_gst',
            ],

            'price_without_gst' => [
                'sometimes',
                'numeric',
                'min:0',
            ],

            'nonmember_price_basis'       => ['required', 'in:without_gst,with_gst'],
            'nonmember_price_without_gst' => ['nullable', 'numeric', 'min:0'],
                        
            'promo_buy' => ['nullable', 'integer', 'min:1'], // added by Darah | promo buy

            'promo_free' => ['nullable', 'integer', 'min:1'],  // added by Darah | promo free

            'compare_price'=>[
                'nullable',
                'numeric'
            ],

            'cost_price'=>[
                'nullable',
                'numeric',
                'min:0',
            ],

            'weight'      => ['nullable', 'numeric', 'min:0.01'],
'weight_unit' => ['nullable', 'in:G,Kg'],
'length'      => ['sometimes', 'required', 'numeric', 'min:0.01'],
'width'       => ['sometimes', 'required', 'numeric', 'min:0.01'],
'height'      => ['sometimes', 'required', 'numeric', 'min:0.01'],

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
            
            'description_ms'=>[ // added by Darah | translation to malay
                'nullable',
                'string'
            ],

            'description_zh'=>[     // added by Darah | translation to chinese
                'nullable',
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
