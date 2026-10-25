<?php

namespace App\Http\Requests;

class UpdateDevicebycompanylistRequest extends UpdateModelRequest
{
    public function getResourceRoute(): string
    {
        return 'deliverybycompanylist';
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
