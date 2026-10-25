<?php

namespace App\Http\Requests;

use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;

class ExportCustomer extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return (bool) (\Auth::user()?->can('create', Customer::class));
    }

    protected function prepareForValidation(): void
    {
        $allChecked = $this->input('all_checked');
        $customerIds = $this->input('customer_ids', []);
        if (! is_array($customerIds)) {
            $customerIds = [];
        }

        if (! in_array($allChecked, ['pagina', 'todos'], true) || ($allChecked === 'pagina' && $customerIds === [])) {
            $allChecked = 'todos';
        }

        $this->merge([
            'all_checked' => $allChecked,
            'separator' => $this->input('separator', 'coma') ?: 'coma',
            'filters' => is_array($this->input('filters')) ? $this->input('filters') : [],
            'customer_ids' => $customerIds,
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
            'all_checked' => 'required|string|in:pagina,todos',
            'customer_ids' => 'nullable|array|required_if:all_checked,pagina',
            'customer_ids.*' => 'integer|distinct|exists:customers,customer_id',
            'separator' => 'nullable|string|in:coma,puntoycoma',
            'filters' => 'nullable|array',
        ];
    }
}
