<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Versione
 *
 * @property int|null $id
 * @property int $company_id
 * @property Carbon|null $fecha
 * @property int $version
 * @package App\Models
 * @method static \Illuminate\Database\Eloquent\Builder|Versione filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Versione newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Versione newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Versione paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Versione query()
 * @method static \Illuminate\Database\Eloquent\Builder|Versione simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Versione whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Versione whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Versione whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Versione whereFecha($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Versione whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Versione whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Versione whereVersion($value)
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Versione',
    description: 'Versione model'
)]
class Versione extends Model
{

    use HasFactory;
    use Filterable;

    protected $table = 'versiones';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'id' => 'int',
        'company_id' => 'int',
        'fecha' => 'datetime',
        'version' => 'int'
    ];

    protected $fillable = [
        'id',
        'company_id',
        'fecha',
        'version'
    ];
}
