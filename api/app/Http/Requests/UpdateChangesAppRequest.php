<?php

namespace App\Http\Requests;

use App\Models\ChangesApp;
use Illuminate\Foundation\Http\FormRequest;

class UpdateChangesAppRequest extends UpdateModelRequest
{
    public function getResourceRoute(): string
    {
        return 'changesapp';
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
