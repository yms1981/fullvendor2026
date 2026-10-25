<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CatalogProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $prodArr = parent::toArray($request);

        $prodArr['star'] = (bool) ($this->has_star_order ?? false);

        return $prodArr;
    }
}
