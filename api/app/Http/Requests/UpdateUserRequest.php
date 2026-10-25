<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends UpdateModelRequest
{
    public function getResourceRoute(): string
    {
        return 'user';
    }


    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->route('user');
        $userId = $user instanceof User ? $user->getKey() : $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($userId),
            ],

            'password' => [
                'nullable',
                'string',
                'min:8',
            ],

            /* Teléfonos opcionales: sin regex rígido (el formulario puede enviar prefijos cortos o vacíos) */
            'phone' => [
                'nullable',
                'string',
                'max:60',
            ],

            'cellphone' => [
                'nullable',
                'string',
                'max:60',
            ],

            'fax' => [
                'nullable',
                'string',
                'max:60',
            ],

            'first_name' => [
                'nullable',
                'string',
                'max:100',
            ],
            'last_name' => [
                'nullable',
                'string',
                'max:100',
            ],
            'address' => [
                'nullable',
                'string',
                'max:255',
            ],
            'city' => [
                'nullable',
                'string',
                'max:100',
            ],
            'state' => [
                'nullable',
                'string',
                'max:100',
            ],
            'zip_code' => [
                'nullable',
                'string',
                'max:30',
            ],

            'image' => [
                'nullable',
                'string',
                'max:255',
            ],

            'seller' => [
                'nullable',
                'array',
            ],
            'seller.phone_number' => [
                'nullable',
                'string',
                'max:60',
            ],
            'seller.cell_number' => [
                'nullable',
                'string',
                'max:60',
            ],
            'seller.fax' => [
                'nullable',
                'string',
                'max:60',
            ],
            'seller.email_on_salesman_order' => [
                'nullable',
                'boolean',
            ],
            'seller.all_customers' => [
                'nullable',
                'boolean',
            ],
            'seller.default' => [
                'nullable',
                'boolean',
            ],
            'seller.proforma' => [
                'nullable',
                'boolean',
            ],
            'seller.profile_id' => [
                'nullable',
                'integer',
                Rule::exists('seller_profiles', 'profile_id'),
            ],
        ];
    }
}
