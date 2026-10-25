<?php

namespace App\Http\Requests;

use App\Models\CatalogSentMail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\App;

class UpdateCatalogSentMailRequest extends UpdateModelRequest
{
    public function getResourceRoute(): string
    {
        return 'catalogsentmail';
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
