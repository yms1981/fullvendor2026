<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCountryRequest extends UpdateModelRequest
{
    public function getResourceRoute(): string
    {
        return 'country';
    }


    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $countryId = $this->route('country');

        return [
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('countries', 'name')->ignore($countryId),
            ],
            'sortname' => [
                'required',
                'string',
                'max:2',
                Rule::unique('countries', 'sortname')->ignore($countryId),
            ],
            'phonecode' => [
                'required',
                'numeric',
                Rule::unique('countries', 'phonecode')->ignore($countryId),
            ],
        ];
    }

    protected function prepareForValidation()
    {
        if ($this->has('sortname')) {
            $this->merge([
                'sortname' => strtoupper($this->input('sortname')),
            ]);
        }
    }
}
