<?php

namespace App\Http\Requests;

use App\Models\Category;
use App\Services\PlanLimitLang;
use App\Support\CompanyRequestLocale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends UpdateModelRequest
{
    public function getResourceRoute(): string
    {
        return 'category';
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $languageId = CompanyRequestLocale::applyFromCompanyId($this->input('company_id'));

        return [
            'category_name' => PlanLimitLang::get($languageId, 'cmp-categorias', 'nombre_categoria'),
        ];
    }

    protected function prepareForValidation(): void
    {
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
            'company_id' => 'required|numeric|exists:companies,company_id',
            'category_name' => [
                'required',
                'string',
                'max:255',
                // Ignorar soft-deleted: tras eliminar se puede volver a usar el mismo nombre.
                Rule::unique('categories')
                    ->where(fn ($q) => $q->where('company_id', $this->company_id)->whereNull('deleted_at'))
                    ->ignore($this->route('category'), 'cat_id'),
            ],
            'category_status' => 'required|boolean',
            'image' => 'nullable|string|exists:images,uuid' // UUID de la imagen
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $languageId = CompanyRequestLocale::applyFromCompanyId($this->input('company_id'));
        $name = (string) $this->input('category_name', '');
        $nameUniqueTemplate = PlanLimitLang::get($languageId, 'cmp-categorias', 'category_name_already_exists');

        return [
            'category_name.unique' => strpos($nameUniqueTemplate, '%') !== false
                ? sprintf($nameUniqueTemplate, $name)
                : $nameUniqueTemplate,
        ];
    }
}
