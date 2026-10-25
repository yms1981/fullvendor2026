<?php

namespace App\Http\Requests;

use App\Models\Company;
use App\Models\Warehouse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class UpdateWarehouseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return \Auth::user()->can('create', Warehouse::class);
    }

    protected function prepareForValidation(): void
    {
        $companyId = $this->input('company_id');
        if (! $companyId) {
            $warehouse = $this->route('warehouse');
            if ($warehouse instanceof Warehouse) {
                $companyId = $warehouse->company_id;
            }
        }
        if ($companyId) {
            $cacheKey = "company_language_{$companyId}";
            $languageId = Cache::remember($cacheKey, 3600, function () use ($companyId) {
                $company = Company::where('company_id', $companyId)->first();

                return $company ? $company->language_id : 1;
            });
            $locale = $languageId == 2 ? 'es' : 'en';
            App::setLocale($locale);
        }

        $this->mergeOptionalLocationIds();
    }

    /**
     * Ciudad y provincia son opcionales; 0 o vacío no deben validarse contra exists.
     */
    protected function mergeOptionalLocationIds(): void
    {
        $merge = [];
        foreach (['city_id', 'state_id'] as $field) {
            $value = $this->input($field);
            if ($value === null || $value === '' || (int) $value < 1) {
                $merge[$field] = null;
            }
        }
        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => __('validation.attributes.name'),
            'company_id' => __('validation.attributes.company_id'),
            'street' => __('validation.attributes.street'),
            'street_number' => __('validation.attributes.street_number'),
            'city_id' => __('validation.attributes.city_id'),
            'state_id' => __('validation.attributes.state_id'),
            'country_id' => __('validation.attributes.country_id'),
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'company_id' => 'required|numeric|exists:companies,company_id',
            'name' => [
                'required',
                'string',
                Rule::unique('warehouses')
                    ->where(fn ($query) => $query->where('company_id', $this->company_id))
                    ->ignore($this->warehouse),
            ],

            //address (solo país obligatorio; resto opcional)
            'street' => 'nullable|string',
            'street_number' => 'nullable|string',
            'city_id' => 'nullable|numeric|exists:cities,id',
            'state_id' => 'nullable|numeric|exists:states,id',
            'country_id' => 'required|numeric|exists:countries,id',
        ];
    }
}
