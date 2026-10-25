<?php

namespace App\Http\Requests;

use App\Models\Company;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return \Auth::user()->can('create', Product::class);
    }

    /**
     * Prepare the data for validation.
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
    public function rules(): array
    {
        return [
            'company_id' => 'required|exists:companies,company_id',
            'sku' => [
                'required',
                'string',
                Rule::unique('products', 'sku')
                    ->where(fn ($query) => $query->where('company_id', $this->company_id)),
            ],
            'category_id'   => 'required|array|min:1',
            'category_id.*' => 'integer|exists:categories,cat_id',
            'name' => 'required|string',
            'sale_price' => 'required|numeric',
            'stock' => 'required|integer|min:0',
            'purchase_price' => 'required|numeric',
            'fob_price' => 'required|numeric',
            'status' => 'boolean'
        ];
    }
}
