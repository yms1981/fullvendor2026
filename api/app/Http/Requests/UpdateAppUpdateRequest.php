<?php

namespace App\Http\Requests;

use App\Models\AppUpdate;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAppUpdateRequest extends UpdateModelRequest
{

    public function getResourceRoute(): string
    {
        return 'appupdate';
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
