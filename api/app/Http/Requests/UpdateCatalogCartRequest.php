<?php

namespace App\Http\Requests;

use App\Models\CatalogCart;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCatalogCartRequest extends UpdateOrderRequest
{
    public function getResourceRoute(): string
    {
        return 'catalogcart';
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
