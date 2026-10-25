<?php

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;

class ExportCategory extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return (bool) (\Auth::user()?->can('create', Category::class));
    }

    protected function prepareForValidation(): void
    {
        $fileType = $this->input('file_type', 'excel');
        $allChecked = $this->input('all_checked');
        $categoryIds = $this->input('category_ids', []);
        if (! is_array($categoryIds)) {
            $categoryIds = [];
        }

        if (! in_array($allChecked, ['pagina', 'todos'], true) || ($allChecked === 'pagina' && $categoryIds === [])) {
            $allChecked = 'todos';
        }

        $this->merge([
            'file_type' => in_array($fileType, ['csv', 'pdf', 'excel'], true) ? $fileType : 'excel',
            'all_checked' => $allChecked,
            'separator' => $this->input('separator', 'coma') ?: 'coma',
            'filters' => is_array($this->input('filters')) ? $this->input('filters') : ['status' => null, 'search' => null],
            'category_ids' => $categoryIds,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'company_id' => 'required|integer|exists:companies,company_id',
            'file_type' => 'required|string|in:csv,pdf,excel',
            'all_checked' => 'required|string|in:pagina,todos',
            'category_ids' => 'nullable|array|required_if:all_checked,pagina',
            'category_ids.*' => 'integer|distinct|exists:categories,cat_id',
            'separator' => 'nullable|string|in:coma,puntoycoma|required_if:file_type,csv',
            'filters' => 'nullable|array',
        ];
    }
}
