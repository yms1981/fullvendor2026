<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRolsRequest extends UpdateModelRequest
{
    /**
     * Get the resource route for this request.
     */
    public function getResourceRoute(): string
    {
        return 'rols';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => 'required|string|max:255',
            'desc_es' => 'required|string',
            'desc_en' => 'required|string',
        ];
    }
}

