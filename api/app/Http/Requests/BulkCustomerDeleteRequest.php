<?php

namespace App\Http\Requests;

use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;

class BulkCustomerDeleteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return \Auth::user()->can('bulkDelete', Customer::class);
    }
    public function rules(): array
    {
        return [
            'company_id' => ['required', 'exists:companies,company_id'],
            'all_checked' =>[ 'required','string','in:pagina,todos'],
            'customer_ids' => ['nullable','array','required_if:all_checked,pagina','min:1'],
            'customer_ids.*' => ['distinct', 'exists:customers,customer_id'],
            'filters' => ['nullable','array', 'required_if:all_checked,todos'],
        ];
    }


}
