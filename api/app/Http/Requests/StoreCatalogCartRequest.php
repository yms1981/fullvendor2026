<?php

namespace App\Http\Requests;

use App\Models\CatalogCart;
use Illuminate\Foundation\Http\FormRequest;

class StoreCatalogCartRequest extends StoreOrderRequest
{
    protected $orderClass = CatalogCart::class;

    /**
     * Si el body viene plano (sin clave "order"), normalizar a order.* para validación.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('order') && is_array($this->input('order'))) {
            return;
        }
        $order = [
            'company_id' => $this->input('company_id'),
            'catalog_id' => $this->input('catalog_id'),
            'tipo_d' => $this->input('tipo_d', 'D'),
            'seller_id' => $this->input('user_id') ?? $this->input('seller_id'),
        ];
        $this->merge(['order' => $order]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_id' => 'required|integer|exists:customers,customer_id',
            'order' => 'nullable|array',
            'order.company_id' => 'required|integer|exists:companies,company_id',
            'order.catalog_id' => 'required|integer|exists:catalog,catalog_id',
            'order.tipo_d' => 'string|in:D,C,I',
            'order.internal_notes' => 'string',
            'order.discount_type' => 'integer',
            'order.discount' => 'decimal:0,4',
            'order.seller_id' => 'nullable|integer',
            'source' => 'string',
        ];
    }
}
