<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = array_merge(parent::toArray($request), [
            'order_details' => OrderDetailResource::collection($this->whenLoaded('order_details')),
        ]);

        $tipoD = strtoupper((string) ($data['tipo_d'] ?? $this->tipo_d ?? 'D'));
        if ($tipoD === 'D') {
            foreach (['amount', 'total_amount', 'total', 'subtotal', 'discount_a'] as $key) {
                if (isset($data[$key]) && is_numeric($data[$key]) && (float) $data[$key] < 0) {
                    $data[$key] = abs((float) $data[$key]);
                }
            }
        }

        return $data;
    }
}
