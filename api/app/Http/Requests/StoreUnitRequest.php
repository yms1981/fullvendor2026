<?php

namespace App\Http\Requests;

use App\Models\Unit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUnitRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return \Auth::user()->can('create', Unit::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'company_id' => 'required|exists:companies,company_id',
            'unit' => [
                'required',
                'string',
                Rule::unique('units')->where(function ($query) {
                    return $query->where('company_id', $this->input('company_id'));
                }),
            ],
            'description' => 'required|string|max:100',
            'formato' => 'required|in:entero,decimal',
            'decimales' => 'required_if:formato,decimal|nullable|integer|min:0|max:9',
            'default' => 'required|boolean'
        ];
    }
}
