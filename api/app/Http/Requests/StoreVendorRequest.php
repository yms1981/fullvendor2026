<?php

namespace App\Http\Requests;

use App\Enums\CompanyUserTypes;
use App\Models\Vendor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVendorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return \Auth::user()->can('create', Vendor::class);
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
                    ->where(fn ($q) => $q->where('company_id', $this->company_id)),
                //unique seller role for this company
                Rule::unique('company_user', 'user_id')
                    ->where(fn($q) => $q
                        ->where('company_id', $this->company_id)
                        ->where('type', CompanyUserTypes::Seller->value)
                    )
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
        ];
    }
}
