<?php

namespace App\Http\Requests;

use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerPortalPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        $customer = $this->route('customer');

        return $customer instanceof Customer
            && (bool) \Auth::user()?->can('update', $customer);
    }

    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'min:1', 'max:255', 'confirmed'],
        ];
    }
}
