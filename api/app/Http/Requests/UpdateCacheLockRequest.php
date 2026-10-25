<?php

namespace App\Http\Requests;

use App\Models\CacheLock;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCacheLockRequest extends UpdateModelRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $item = $this->route('cachelock');
        return \Auth::user()->can('update', $item);
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

    public function getResourceRoute(): string
    {
        return 'cachelock';
    }
}
