<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'firstname' => ['required','string','max:100'],

            'lastname' => ['required','string','max:100'],

            'username' => [

                'required',

                'string',

                Rule::unique('users')->ignore(

                    $this->user()->id

                ),

            ],

            'email' => [

                'required',

                'email',

                Rule::unique('users')->ignore(

                    $this->user()->id

                ),

            ],

            'phone' => ['nullable', 'string', 'max:30'],

        ];
    }
}
