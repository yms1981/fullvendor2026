<?php

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class BulkCategoryDeleteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::user()->can('bulkDelete', Category::class);
    }

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'integer'],
            'all_checked' => ['required', 'string', 'in:pagina,todos'],
            'category_ids' => ['nullable', 'array', 'required_if:all_checked,pagina'],
            'category_ids.*' => ['integer', 'distinct'],
            'filters' => ['nullable', 'array', 'required_if:all_checked,todos'],
        ];
    }
}
