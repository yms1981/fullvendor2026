<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class RsRequested
 *
 * @property int $requested
 * @property int $product_id
 * @property string|null $name
 * @property float|null $qty
 * @property int|null $company_id
 * @property int $customer_id
 * @package App\Models
 * @method static \Illuminate\Database\Eloquent\Builder|RsRequested filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|RsRequested newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RsRequested newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RsRequested paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|RsRequested query()
 * @method static \Illuminate\Database\Eloquent\Builder|RsRequested simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|RsRequested whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|RsRequested whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RsRequested whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RsRequested whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|RsRequested whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|RsRequested whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RsRequested whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RsRequested whereQty($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RsRequested whereRequested($value)
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Rsrequested',
    description: 'RsRequested model'
)]
class RsRequested extends Model
{

    use HasFactory;
    use Filterable;

    protected $table = 'rs_requested';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'requested' => 'int',
        'product_id' => 'int',
        'qty' => 'float',
        'company_id' => 'int',
        'customer_id' => 'int'
    ];

    protected $fillable = [
        'requested',
        'product_id',
        'name',
        'qty',
        'company_id',
        'customer_id'
    ];
}
