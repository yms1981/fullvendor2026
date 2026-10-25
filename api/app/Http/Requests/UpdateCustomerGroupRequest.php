<?php

namespace App\Http\Requests;

use App\Enums\CustomerGroupTypes;
use App\Services\PlanLimitLang;
use App\Support\CompanyRequestLocale;
use Illuminate\Validation\Rule;

class UpdateCustomerGroupRequest extends UpdateModelRequest
{
    public function getResourceRoute(): string
    {
        return 'customergroup';
    }

    /**
     * Prepare the data for validation (normalize "ninguno" and empty percent).
     */
    protected function prepareForValidation(): void
    {
        $normalized = CustomerGroupTypes::normalize($this->input('percentage_on_price'));
        if ($normalized === false) {
            CompanyRequestLocale::applyFromCompanyId($this->input('company_id'));

            return;
        }

        $this->merge([
            'percentage_on_price' => $normalized,
            'percent_price_amount' => $normalized === null
                ? 0
                : $this->input('percent_price_amount', 0),
        ]);

        CompanyRequestLocale::applyFromCompanyId($this->input('company_id'));
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
            'name' => [
                'required',
                Rule::unique('customer_groups')
                    ->where(fn ($q) => $q->where('company_id', $this->company_id))
                    ->ignore($this->route('customergroup')),
            ],
            'percentage_on_price' => [
                'nullable',
                Rule::in(array_merge([null, '', 'nothing'], CustomerGroupTypes::all())),
            ],
            'percent_price_amount' => [
                'nullable',
                Rule::requiredIf(fn () => in_array(
                    $this->input('percentage_on_price'), CustomerGroupTypes::all(),
                )),
                'numeric',
                'gte:0'
            ],
            'default' => ['boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $languageId = CompanyRequestLocale::applyFromCompanyId($this->input('company_id'));
        $name = (string) $this->input('name', '');
        $nameUniqueTemplate = PlanLimitLang::get($languageId, 'cmp-group', 'group_name_already_exists');

        return [
            'name.unique' => strpos($nameUniqueTemplate, '%') !== false
                ? sprintf($nameUniqueTemplate, $name)
                : $nameUniqueTemplate,
            'percent_price_amount.gte' => PlanLimitLang::get($languageId, 'cmp-group', 'group_percent_gte'),
        ];
    }
}
