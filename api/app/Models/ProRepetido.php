<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ProRepetido
 *
 * @property int|null $company_id
 * @property string|null $sku
 * @property int $cuenta
 * @package App\Models
 * @method static \Database\Factories\ProRepetidoFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|ProRepetido filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|ProRepetido newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProRepetido newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProRepetido paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|ProRepetido query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProRepetido simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|ProRepetido whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|ProRepetido whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProRepetido whereCuenta($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProRepetido whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|ProRepetido whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|ProRepetido whereSku($value)
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Prorepetido',
    description: 'ProRepetido model'
)]
class ProRepetido extends Model
{

    use HasFactory;
    use Filterable;

    protected $table = 'pro_repetidos';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'company_id' => 'int',
        'cuenta' => 'int'
    ];

    protected $fillable = [
        'company_id',
        'sku',
        'cuenta'
    ];
}
