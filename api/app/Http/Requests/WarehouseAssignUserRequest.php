<?php

namespace App\Http\Requests;

use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;

class WarehouseAssignUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return \Auth::user()->can('create', Customer::class);
    }
    public function rules(): array
    {
        return [
            'company_id' => ['required', 'numeric', 'exists:companies,company_id'],
            'user_id' => ['required', 'numeric', 'exists:users,id']
        ];
    }
}
