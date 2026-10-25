<?php

namespace App\Http\Requests;

use App\Models\Cm;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCompaniesCategoryRequest extends UpdateModelRequest
{
    public function getResourceRoute() : string {
        return 'companiescategory';
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
