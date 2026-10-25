<?php

namespace App\Http\Requests;

use App\Models\CompanyContact;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyContactRequest extends UpdateModelRequest
{
    public function getResourceRoute(): string
    {
        return 'companycontact';
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
