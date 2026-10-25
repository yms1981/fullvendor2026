<?php

namespace App\Http\Requests;

use App\Models\Country;
use Illuminate\Foundation\Http\FormRequest;

class StoreCountryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return \Auth::user()->can('create', Country::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:150|unique:countries,name',
            'sortname' => 'required|string|max:2|unique:countries,sortname',
            'phonecode' => 'required|numeric|unique:countries,phonecode',
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
