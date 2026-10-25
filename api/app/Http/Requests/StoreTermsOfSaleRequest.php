<?php

namespace App\Http\Requests;

use App\Models\Company;
use App\Models\TermsOfSale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class StoreTermsOfSaleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return \Auth::user()->can('create', TermsOfSale::class);
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Establecer el locale basado en el language_id de la compañía
        $companyId = $this->input('company_id');
        if ($companyId) {
            $cacheKey = "company_language_{$companyId}";
            $languageId = Cache::remember($cacheKey, 3600, function () use ($companyId) {
                $company = Company::where('company_id', $companyId)->first();
                return $company ? $company->language_id : 1; // Default a inglés (1)
            });
            
            // Mapear language_id a locale: 1 = inglés (en), 2 = español (es)
            $locale = $languageId == 2 ? 'es' : 'en';
            App::setLocale($locale);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'company_id' => 'required|exists:companies,company_id',
            'name' => [
                'required',
                'string',
                Rule::unique('terms_of_sales')->where(function ($query) {
                    return $query
                        ->where('company_id', $this->input('company_id'))
                        ->whereNull('deleted_at');
                }),
            ],
            'default' => 'nullable|boolean'
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        $termName = $this->input('name', '');
        $locale = App::getLocale();
        
        if ($locale === 'es') {
            return [
                'name.unique' => "El nombre del termino de venta {$termName} ya existe.",
            ];
        } else {
            return [
                'name.unique' => "The term of sale name {$termName} already exists.",
            ];
        }
    }
}
