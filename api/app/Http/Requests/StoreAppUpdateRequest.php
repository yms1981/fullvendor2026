<?php

namespace App\Http\Requests;

use App\Models\AppUpdate;
use Illuminate\Foundation\Http\FormRequest;

class StoreAppUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return \Auth::user()->can('create', AppUpdate::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'company_id' => 'required|integer|exists:companies,company_id',
            'updatenumber' => 'required|integer',
        ];
    }
}
