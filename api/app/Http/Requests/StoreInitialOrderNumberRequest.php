<?php

namespace App\Http\Requests;

use App\Models\InitialOrderNumber;
use Illuminate\Foundation\Http\FormRequest;

class StoreInitialOrderNumberRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return \Auth::user()->can('create', InitialOrderNumber::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'company_id' => ['required', 'integer', 'min:1'],
            'initial_number' => ['required', 'integer', 'min:1'],
        ];
    }
}
