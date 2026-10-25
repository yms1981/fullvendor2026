<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;

/**
 * Solo valida que el body sea un arreglo de órdenes.
 * Cada orden se valida y procesa de forma independiente en el controller
 * para no detener el lote si una falla.
 */
class ImportOrdersBulkPublicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Lotes grandes: evitar timeout / memoria en normalización previa al controller.
        set_time_limit(0);
        if (function_exists('ini_set')) {
            @ini_set('memory_limit', '512M');
            @ini_set('max_input_time', '0');
        }

        $raw = $this->json()->all();
        if (! is_array($raw) || $raw === []) {
            $raw = $this->all();
        }

        // Misma estructura que order/import, pero como arreglo raíz:
        // [ { company_id, cabezal, items }, { company_id, cabezal, items }, ... ]
        if (is_array($raw) && array_is_list($raw)) {
            $orders = $raw;
        } elseif (is_array($raw) && isset($raw[0]) && is_array($raw[0])) {
            $orders = $raw;
        } else {
            $orders = $raw['orders'] ?? $raw['ordenes'] ?? $raw['data'] ?? [];
            if (! is_array($orders)) {
                $orders = [];
            }
        }

        $normalized = [];
        foreach ($orders as $order) {
            if (! is_array($order)) {
                $normalized[] = ['_invalid' => true, '_raw' => $order];
                continue;
            }
            $normalized[] = ImportOrderPublicRequest::normalizeOrderPayload($order);
        }

        $this->replace([
            'orders' => $normalized,
        ]);
    }

    public function rules(): array
    {
        return [
            'orders' => ['required', 'array', 'min:1', 'max:500'],
            'orders.*' => ['required', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'orders.required' => 'Debe enviar un arreglo JSON de órdenes (misma estructura que order/import).',
            'orders.min' => 'Debe enviar al menos una orden.',
            'orders.max' => 'Máximo 500 órdenes por solicitud.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new \Illuminate\Validation\ValidationException($validator, response()->json([
            'message' => 'Error de validación en el lote de órdenes.',
            'error' => 'validation_error',
            'errors' => $validator->errors(),
        ], 422));
    }
}
