<?php

namespace App\Http\Requests;

use App\Models\CatalogRequest;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCatalogRequestRequest extends UpdateModelRequest
{
    public function getResourceRoute() : string{
        return 'catalogrequest';
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
