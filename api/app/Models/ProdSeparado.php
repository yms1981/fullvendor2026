<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ProdSeparado
 *
 * @property int $pro_id
 * @property string|null $category_value
 * @property int|null $company_id
 * @package App\Models
 * @method static \Database\Factories\ProdSeparadoFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|ProdSeparado filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|ProdSeparado newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProdSeparado newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProdSeparado paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|ProdSeparado query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProdSeparado simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|ProdSeparado whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|ProdSeparado whereCategoryValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProdSeparado whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProdSeparado whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|ProdSeparado whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|ProdSeparado whereProId($value)
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Prodseparado',
    description: 'ProdSeparado model'
)]
class ProdSeparado extends Model
{

    use HasFactory;
    use Filterable;

    protected $table = 'prodSeparados';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'pro_id' => 'int',
        'company_id' => 'int'
    ];

    protected $fillable = [
        'pro_id',
        'category_value',
        'company_id'
    ];
}
