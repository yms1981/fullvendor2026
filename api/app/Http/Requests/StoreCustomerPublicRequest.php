<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerPublicRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * No requiere autenticación - siempre retorna true
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules()
    {
        return [
            'company_id' => ['required', 'numeric', 'exists:companies,company_id'],
            'name' => ['nullable', 'string', 'max:255'],
            'business_name' => ['required', 'string', 'max:255','unique:customers,business_name'],
            'tax_id' => ['nullable', 'string', 'max:255'],
            'discount' => ['nullable', 'numeric', 'between:0,100'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'cell_phone' => ['nullable', 'string', 'max:20'],
            'catalog_emails' => ['nullable', 'boolean'],
            'sellers' => ['nullable', 'array'],
            'sellers.*' => ['numeric', 'distinct', 'exists:sellers,id'],
            'seller_assign_catalog' => ['nullable', 'numeric', 'exists:sellers,id'],
            'term_id' => ['required', 'numeric', 'exists:terms_of_sales,term_id'],
            'group_id' => ['required', 'numeric', 'exists:customer_groups,group_id'],
            'cust_id_kor' => ['nullable', 'string', 'max:50'],
            'id_kor' => ['nullable', 'integer', 'min:0'],

            'commercial_address' => ['nullable', 'string', 'max:255'],
            'commercial_delivery_address' => ['nullable', 'string', 'max:255'],
            'commercial_country' => ['nullable', 'numeric', 'exists:countries,id'],
            'commercial_state' => ['nullable', 'string', 'max:255'],
            'commercial_city' => ['nullable', 'string', 'max:255'],
            'commercial_zip_code' => ['nullable', 'string', 'max:20'],
            'commercial_zone' => ['nullable', 'string', 'max:100'],

            'dispatch_address' => ['nullable', 'string', 'max:255'],
            'dispatch_state' => ['nullable', 'string', 'max:255'],
            'dispatch_country' => ['nullable', 'numeric', 'exists:countries,id'],
            'dispatch_city' => ['nullable', 'string', 'max:255'],
            'dispatch_zip_code' => ['nullable', 'string', 'max:20'],
            'dispatch_zone' => ['nullable', 'string', 'max:100'],
            'dispatch_shipping_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

}
