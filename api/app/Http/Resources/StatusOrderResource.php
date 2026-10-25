<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StatusOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);
        
        // Agregar campo 'name' basado en el idioma del usuario
        // Obtener language_id de los query parameters o del usuario autenticado
        $langId = $request->query('language_id') 
            ?? $request->input('language_id')
            ?? $request->user()?->company?->language_id 
            ?? 1;
        
        // Determinar qué campo usar según el idioma
        // 1 = inglés, 2 = español, otros = inglés por defecto
        if ($langId == 2) {
            $data['name'] = $this->name_status_spanish ?? '';
        } else {
            $data['name'] = $this->name_status_english ?? '';
        }
        
        // Mantener compatibilidad con name_status para código existente
        $data['name_status'] = $data['name'];
        
        return $data;
    }
}
