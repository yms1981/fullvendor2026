<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreImageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return \Auth::user()->can('create', \App\Models\Image::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        if ($this->hasFile('contents')) {
            return [
                // Acepta cualquier archivo de imagen; la normalización/detección ocurre en Image::putContent.
                'contents' => ['required', 'file', 'max:102400'],
                'name' => ['nullable', 'string', 'max:255'],
                'company_id' => ['nullable'],
                'type' => ['nullable', 'string', 'in:product,category,company,user,chat,catalog'],
            ];
        }

        return [
            'contents' => ['nullable'],
            'name' => ['nullable', 'string', 'max:255'],
            'company_id' => ['nullable'],
            'type' => ['nullable', 'string', 'in:product,category,company,user,chat,catalog'],
        ];
    }
}
