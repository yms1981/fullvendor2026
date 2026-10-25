<?php

namespace App\Http\Requests;

use App\Enums\OrderStatusCodes;
use Illuminate\Foundation\Http\FormRequest;

class PlaceOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_placed_order'          => 'required|exists:users,id',
            'source'           => 'required|string',
            'proforma'         => 'nullable|boolean', //now, form only send it when is "true", make it required and send 0|1?
            'new_address'      => 'nullable|boolean', //now, form only send it when is "true", make it required and send 0|1?
            'delivery_address' => 'required_if:new_address,true|nullable|string',
            'zip_code'         => 'required_if:new_address,true|nullable|string',
            'country'          => 'required_if:new_address,true|nullable|exists:countries,id',
            'state'            => 'required_if:new_address,true|nullable|exists:states,id',
            'city'             => 'required_if:new_address,true|nullable|exists:cities,id',
            'zone'             => 'required_if:new_address,true|nullable|string',
            'shipping_notes'   => 'nullable|string',
            'comment_order'    => 'nullable|string',
        ];
    }

    public function validated($key = null, $default = null): array
    {
        $validated = parent::validated();

        return array_merge(
            $this->cleanValidatedData($validated),
            [
                'new_address' => $this->buildNewAddress($validated),
                'notes'       => $this->buildNotes($validated)
            ]
        );
    }

    private function buildNewAddress(array $validated): ?array
    {
        if (empty($validated['new_address'])) {
            return null;
        }

        return [
            'delivery_address' => $validated['delivery_address'],
            'zip_code'         => $validated['zip_code'],
            'city'             => $validated['city'],
            'state'            => $validated['state'],
            'country'          => $validated['country'],
            'zone'             => $validated['zone']
        ];
    }

    private function buildNotes(array $validated): ?array
    {
        if (empty($validated['shipping_notes']) && empty($validated['comment_order'])) {
            return null;
        }

        return [
            'shipping_notes'   => $validated['shipping_notes'] ?? null,
            'comment_order'    => $validated['comment_order'] ?? null
        ];
    }

    private function cleanValidatedData(array $validated): array
    {
        return collect($validated)->except([
            'delivery_address',
            'zip_code',
            'zone',
            'city',
            'state',
            'country',
            'shipping_notes',
            'comment_order',
        ])->toArray();
    }
}
