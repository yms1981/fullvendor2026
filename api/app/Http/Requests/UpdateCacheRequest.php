<?php

namespace App\Http\Requests;

use App\Models\Cache;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCacheRequest extends UpdateModelRequest
{

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

    public function getResourceRoute(): string
    {
        return 'cache';
    }
}
