<?php

namespace App\Http\Requests\SellerPortal;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SellerPortalStoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->attributes->get('seller_portal_vendor') !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'customer_id' => 'required|exists:customers,customer_id',
            'seller_id' => [
                'nullable',
                'integer',
                Rule::when(fn () => (int) $this->input('seller_id', 0) !== 0, 'exists:sellers,id'),
            ],
            'discount_type' => 'required|in:1,2',
            'discount' => 'nullable|numeric|min:0',
            'proforma' => 'nullable|boolean',
            'internal_comments' => 'nullable|string',
            'source' => 'nullable|string|max:50',
            'tipo_d' => 'nullable|string|in:D,C,I',
            'products' => 'required|array|min:1',
            'products.*.pro_id' => 'required|exists:products,pro_id',
            'products.*.qty' => 'required|numeric|gt:0',
            'products.*.price' => 'required|numeric|gt:0',
            'products.*.fixed_price' => 'required|boolean',
            'products.*.comment' => 'nullable|string',
        ];
    }
}
