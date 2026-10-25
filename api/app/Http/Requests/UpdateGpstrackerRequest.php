<?php

namespace App\Http\Requests;

class UpdateGpstrackerRequest extends UpdateModelRequest
{
    public function getResourceRoute(): string
    {
        return 'gpstracker';
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
