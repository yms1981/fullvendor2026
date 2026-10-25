<?php

namespace App\Http\Requests;

use App\Models\DetailCar;
use Illuminate\Foundation\Http\FormRequest;

class StoreDetailCarRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return \Auth::user()->can('create', DetailCar::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'order_id' => 'required|int|exists:orders,order_id',
            'product_id' => 'required|int|exists:products,pro_id',
            'company_id' => 'required|int|exists:companies,company_id',
            'delivered_quantity' => 'required|decimal:3',
            'discount' => 'required|decimal:3',
            'sale_price' => 'decimal:3',
            'comment' => 'string',
            'comments' => 'string',
        ];
    }
}
