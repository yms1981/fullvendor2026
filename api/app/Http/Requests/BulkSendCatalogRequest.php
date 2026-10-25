<?php

namespace App\Http\Requests;

use App\Models\Company;
use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;

class BulkSendCatalogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return \Auth::user()->can('create', Customer::class);
    }

    /**
     * Alinear locale de la app con el idioma de la compañía (mensajes de validación y respuestas).
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

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'exists:companies,company_id'],
            'all_checked' =>[ 'required','string','in:pagina,todos'],
            'customer_ids' => ['nullable','array','required_if:all_checked,pagina','min:1'],
            'customer_ids.*' => ['distinct', 'exists:customers,customer_id'],
            'filters' => ['nullable','array', 'required_if:all_checked,todos'],

            'catalogs' => 'required|array|min:1',
            'catalogs.*' => 'distinct|exists:catalog,catalog_id',

            'expiration_days' => 'required|integer|min:1',
            'catalog_title' => ['nullable', 'string', 'max:5000'],
            'email_text' => ['nullable', 'string', 'max:10000'],
            'type' => 'required|string|in:email,sms,whatsapp',
        ];
    }
}
