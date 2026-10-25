<?php

namespace App\Models;

use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OpenApi\Attributes as OA;

/**
 * Class Format
 *
 * Formatos de impresión/descarga de catálogos (tabla `formats`).
 *
 * @property int $format_id
 * @property string $format_name
 * @property string|null $format_description
 * @property string|null $template
 * @property int|null $order
 * @property int|null $is_default
 * @property int|null $company_id
 * @property string|null $format_type
 * @property int|null $related_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @package App\Models
 */
#[OA\Schema(
    schema: 'Format',
    description: 'Format model'
)]
class Format extends Model
{
    use HasFactory;
    use Filterable;
    use SoftDeletes;

    protected $table = 'formats';
    protected $primaryKey = 'format_id';

    protected $casts = [
        'order' => 'int',
        'is_default' => 'int',
        'company_id' => 'int',
        'related_id' => 'int',
    ];

    protected $fillable = [
        'format_name',
        'format_description',
        'template',
        'order',
        'is_default',
        'company_id',
        'format_type',
        'related_id',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'company_id');
    }
}
