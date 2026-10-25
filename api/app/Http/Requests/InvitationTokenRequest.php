<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InvitationTokenRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'customer_id' => 'required|integer|exists:customers,customer_id',
            'seller_id' => 'nullable|integer|exists:sellers,id',
            'catalog_id' => 'required|integer|exists:catalog,catalog_id',
            'catalog_text' => 'nullable|string',
        ];
    }
}
