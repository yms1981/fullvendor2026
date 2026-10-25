<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\Validator as ValidatorFacade;

class UpdateProductStockPublicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $raw = $this->json()->all();
        if (! is_array($raw) || $raw === []) {
            $raw = $this->all();
        }

        // Formatos aceptados:
        // 1) { company_id, sku, stock, fob_price, purchase_price }
        // 2) { company_id, items: [ { sku, stock, fob_price, purchase_price }, ... ] }
        // 3) [ { company_id, sku, stock, fob_price, purchase_price }, ... ]
        if (is_array($raw) && array_is_list($raw)) {
            $items = $raw;
        } elseif (isset($raw['sku']) || isset($raw['SKU'])) {
            $items = [$raw];
        } else {
            $companyId = $raw['company_id'] ?? $raw['compania_id'] ?? null;
            $list = $raw['items'] ?? $raw['products'] ?? $raw['productos'] ?? [];
            if (! is_array($list)) {
                $list = [];
            }
            $items = [];
            foreach ($list as $row) {
                if (! is_array($row)) {
                    continue;
                }
                if (! isset($row['company_id']) && ! isset($row['compania_id']) && $companyId !== null) {
                    $row['company_id'] = $companyId;
                }
                $items[] = $row;
            }
        }

        $normalized = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $normalized[] = self::normalizeItem($item);
        }

        $this->replace(['items' => $normalized]);
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    public static function normalizeItem(array $item): array
    {
        $stock = $item['stock'] ?? $item['qty'] ?? $item['quantity'] ?? null;
        if (is_numeric($stock) && (float) $stock < 0) {
            $stock = 0;
        }

        return [
            'company_id' => $item['company_id'] ?? $item['compania_id'] ?? null,
            'sku' => $item['sku'] ?? $item['SKU'] ?? null,
            'stock' => $stock,
            'fob_price' => $item['fob_price'] ?? $item['fob'] ?? null,
            'purchase_price' => $item['purchase_price'] ?? $item['purchase'] ?? $item['cost'] ?? null,
        ];
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1', 'max:2000'],
            'items.*' => ['required', 'array'],
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array{ok:true,data:array}|array{ok:false,errors:array}
     */
    public static function validateItem(array $item): array
    {
        $validator = ValidatorFacade::make($item, [
            'company_id' => ['required', 'integer', 'exists:companies,company_id'],
            'sku' => ['required', 'string', 'max:255'],
            'stock' => ['required', 'numeric', 'min:0'],
            'fob_price' => ['required', 'numeric', 'min:0'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
        ]);

        if ($validator->fails()) {
            return ['ok' => false, 'errors' => $validator->errors()->toArray()];
        }

        return ['ok' => true, 'data' => $validator->validated()];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Debe enviar al menos un producto (objeto o arreglo).',
            'items.min' => 'Debe enviar al menos un producto.',
            'items.max' => 'Máximo 2000 productos por solicitud.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new \Illuminate\Validation\ValidationException($validator, response()->json([
            'message' => 'Error de validación.',
            'error' => 'validation_error',
            'errors' => $validator->errors(),
        ], 422));
    }
}
