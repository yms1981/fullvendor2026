<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStateRequest extends UpdateModelRequest
{
    public function getResourceRoute(): string
    {
        return 'state';
    }


    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $stateId = $this->route('state');

        return [
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('states')
                    ->ignore($stateId)
                    ->where(fn ($query) => $query
                        ->where('country_id', $this->input('country_id'))
                    ),
            ],
            'country_id' => [
                'required',
                'exists:countries,id',
            ],
        ];
    }
}
