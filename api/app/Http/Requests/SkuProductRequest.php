<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpFoundation\Request;

class SkuProductRequest extends FormRequest
{
    public function rules(): array {
        return
            [
                'company_id' => 'required|integer|exists:companies,company_id',
            ];
    }

}
