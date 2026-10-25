<?php

namespace App\Http\Requests;

use App\Models\Company;
use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class StoreCustomerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return \Auth::user()->can('create', Customer::class);
    }

    /**
     * Set app locale from company language so validation messages are in the correct language.
     */
    protected function prepareForValidation(): void
    {
        $companyId = $this->input('company_id');
        if ($companyId) {
            $cacheKey = "company_language_{$companyId}";
            $languageId = Cache::remember($cacheKey, 3600, function () use ($companyId) {
                $company = Company::where('company_id', $companyId)->first();
                return $company ? $company->language_id : 1;
            });
            $locale = $languageId == 2 ? 'es' : 'en';
            App::setLocale($locale);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function attributes(): array
    {
        return [
            'name' => __('validation.attributes.customer_contact_name'),
            'business_name' => __('validation.attributes.business_name'),
            'email' => __('validation.attributes.email'),
        ];
    }

    public function rules()
    {
        $companyId = $this->input('company_id');

        return [
            'company_id' => ['required', 'numeric', 'exists:companies,company_id'],
            'name' => ['nullable', 'string', 'max:255'],
            'business_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('customers', 'business_name')
                    ->where(static fn ($query) => $query->where('company_id', $companyId)),
            ],
            'tax_id' => ['nullable', 'string', 'max:255'],
            'discount' => ['nullable', 'numeric', 'between:0,100'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
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
            'commercial_state_id' => ['nullable', 'integer', 'exists:states,id'],
            'commercial_city' => ['nullable', 'string', 'max:255'],
            'commercial_city_id' => ['nullable', 'integer', 'exists:cities,id'],
            'commercial_zip_code' => ['nullable', 'string', 'max:20'],
            'commercial_zone' => ['nullable', 'string', 'max:100'],

            'dispatch_address' => ['nullable', 'string', 'max:255'],
            'dispatch_delivery_address' => ['nullable', 'string', 'max:500'],
            'dispatch_state' => ['nullable', 'string', 'max:255'],
            'dispatch_state_id' => ['nullable', 'integer', 'exists:states,id'],
            'dispatch_country' => ['nullable', 'numeric', 'exists:countries,id'],
            'dispatch_city' => ['nullable', 'string', 'max:255'],
            'dispatch_city_id' => ['nullable', 'integer', 'exists:cities,id'],
            'dispatch_zip_code' => ['nullable', 'string', 'max:20'],
            'dispatch_zone' => ['nullable', 'string', 'max:100'],
            'dispatch_shipping_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

}
