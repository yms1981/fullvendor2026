<?php

namespace App\Http\Requests;

use App\Models\CompanyStatusOrder;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyStatusOrderRequest extends UpdateModelRequest
{
    public function getResourceRoute(): string
    {
        return 'companystatusorder';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            //
        ];
    }
}
