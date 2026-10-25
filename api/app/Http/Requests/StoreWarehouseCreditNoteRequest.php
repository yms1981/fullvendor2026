<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación para nota de crédito creada desde warehouse.
 * warehouse_user_id (obligatorio) → orders.warehouse_user_id (users.id del almacenero).
 * seller_id es opcional (cliente / default compañía).
 * tipo_d se fuerza a C en el servicio.
 */
class StoreWarehouseCreditNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'company_id' => 'nullable|integer',
            'customer_id' => 'required|exists:customers,customer_id',
            'warehouse_user_id' => ['required', 'integer', 'exists:users,id'],
            'seller_id' => [
                'nullable',
                'integer',
                Rule::when(
                    fn () => (int) $this->input('seller_id') > 0,
                    ['exists:sellers,id']
                ),
            ],
            'discount_type' => 'required|in:1,2,"1","2"',
            'discount' => 'nullable|numeric',
            'proforma' => 'nullable|boolean',
            'internal_comments' => 'nullable|string',
            'source' => 'nullable|string|max:50',
            'products' => 'required|array|min:1',
            'products.*.pro_id' => 'required|integer|exists:products,pro_id',
            'products.*.qty' => 'required|numeric|gt:0',
            // Precio puede ser 0 en NC de cortesía desde la app.
            'products.*.price' => 'required|numeric|min:0',
            'products.*.fixed_price' => 'nullable|boolean',
            'products.*.comment' => 'nullable|string',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Alias habituales de la app móvil.
        if (! $this->filled('warehouse_user_id')) {
            $alias = $this->input('warehouse_user')
                ?? $this->input('warehouseUser')
                ?? $this->input('user_id');
            if ($alias !== null && $alias !== '') {
                $this->merge(['warehouse_user_id' => (int) $alias]);
            }
        }

        $products = $this->input('products');
        if (! is_array($products)) {
            return;
        }

        foreach ($products as $i => $row) {
            if (! is_array($row)) {
                continue;
            }
            if (! array_key_exists('fixed_price', $row) || $row['fixed_price'] === null || $row['fixed_price'] === '') {
                $products[$i]['fixed_price'] = false;
            }
        }

        $this->merge(['products' => $products]);
    }
}
