<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateVendorRequest extends UpdateModelRequest
{
    public function getResourceRoute(): string
    {
        return 'vendor';
    }


    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'company_id' => ['required', 'numeric', 'exists:companies,company_id'],
            'user_id' => [
                'required',
                'numeric',
                'exists:users,id',
                //unique seller for this company
                Rule::unique('sellers')
                    ->where(fn ($q) => $q->where('company_id', $this->company_id))
                    ->ignore($this->vendor->id, 'id'),
            ],
            'profile_id' => [
                'required',
                'numeric',
                Rule::exists('seller_profiles','profile_id'),
            ],
            'phone_number' => ['required', 'string', 'regex:/^\+?[0-9\s\-()]{7,20}$/'],
            'cell_number' => ['required', 'string', 'regex:/^\+?[0-9\s\-()]{7,20}$/'],
            'fax' => ['nullable', 'string', 'max:20'],
            'email_on_salesman_order' => ['required', 'boolean'],
            'all_customers' => ['required', 'boolean'],
            'default' => ['required', 'boolean'],
            'proforma' => ['required', 'boolean'],
            'status' => ['nullable', 'boolean'],
        ];
    }
}
