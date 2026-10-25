<?php

namespace App\Http\Requests;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    protected $orderClass = Order::class;
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return \Auth::user()->can('create', $this->orderClass);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_id' => 'required|exists:customers,customer_id',
            'seller_id' => [
                'required',
                'integer',
                Rule::when(fn () => (int) $this->input('seller_id') !== 0, 'exists:sellers,id'),
            ],
            'discount_type' => 'required|in:1,2', //1 = %, 2 = $
            'discount' => 'decimal:0,4',
            'proforma' => 'boolean',
            'internal_comments' => 'nullable|string',
            'source' => 'nullable|string|max:50',
            'tipo_d' => 'nullable|string|in:D,C,I',
            'cart_id' => 'nullable|integer',
            'token_kor' => 'nullable|string',
            'products' => 'required|array|min:1',

            'products.*.pro_id' => 'required|exists:products,pro_id',
            'products.*.qty' => 'required|numeric|gt:0',
            'products.*.price' => 'required|numeric|gt:0',
            'products.*.fixed_price' => 'required|boolean',
            'products.*.comment' => 'nullable|string',
        ];
    }
}
