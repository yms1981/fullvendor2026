<?php

namespace App\Http\Requests;

use App\Models\CacheLock;
use Illuminate\Foundation\Http\FormRequest;

class StoreCacheLockRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return \Auth::user()->can('create', CacheLock::class);
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
            'owner' => 'bail|required|string',
            'expiration' => 'bail|required|integer',
        ];
    }
}
