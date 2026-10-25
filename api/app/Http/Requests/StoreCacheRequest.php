<?php

namespace App\Http\Requests;

use App\Models\Cache;
use Illuminate\Foundation\Http\FormRequest;

class StoreCacheRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return \Auth::user()->can('create', Cache::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'key' => 'bail|required|string',
            'value' => 'bail|required|string',
            'expiration' => 'integer|nullable'
        ];
    }
}
