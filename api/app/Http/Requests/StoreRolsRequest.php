<?php

namespace App\Http\Requests;

use App\Models\Rols;
use Illuminate\Foundation\Http\FormRequest;

class StoreRolsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return \Auth::user()->can('create', Rols::class);
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

