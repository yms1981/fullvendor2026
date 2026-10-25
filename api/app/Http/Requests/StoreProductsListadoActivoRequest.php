<?php

namespace App\Http\Requests;

use App\Models\ProductsListadoActivo;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductsListadoActivoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return \Auth::user()->can('create', ProductsListadoActivo::class);
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
