<?php

namespace App\Http\Requests;

use App\Models\City;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCityRequest extends UpdateModelRequest
{
    public function getResourceRoute(): string
    {
        return 'city';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $stateId = $this->route('city');

        return [
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('cities')
                    ->ignore($stateId)
                    ->where(fn ($query) => $query
                        ->where('state_id', $this->input('state_id'))
                    ),
            ],
            'state_id' => [
                'required',
                'exists:states,id',
            ],
        ];
    }
}
