<?php

namespace App\Models;

use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rols extends Model
{
    use HasFactory;
    use Filterable;

    protected $table = 'rols';
    protected $primaryKey = 'rol_id';

    // Timestamps habilitados por defecto (created_at y updated_at se llenan automáticamente)
    // Los campos que se guardan son:
    // - rol_id (id): se genera automáticamente como auto-increment
    // - type: viene del formulario
    // - desc_es: viene del formulario
    // - desc_en: viene del formulario
    // - created_at: se llena automáticamente por Laravel al crear
    // - updated_at: se llena automáticamente por Laravel al crear/actualizar

    protected $fillable = [
        'type',
        'desc_es',
        'desc_en'
    ];
}

