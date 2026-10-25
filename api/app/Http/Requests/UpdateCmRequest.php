<?php

namespace App\Http\Requests;

use App\Models\Cm;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCmRequest extends UpdateModelRequest
{
    public function getResourceRoute(): string
    {
        return 'cm';
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {

        return \Auth::user()->can('update', $this->route('cm'));
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
