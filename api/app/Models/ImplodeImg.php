<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ImplodeImg
 *
 * @property int|null $company_id
 * @property int|null $product_id
 * @property string|null $imploded
 * @package App\Models
 * @method static \Database\Factories\ImplodeImgFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|ImplodeImg filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|ImplodeImg newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ImplodeImg newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ImplodeImg paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|ImplodeImg query()
 * @method static \Illuminate\Database\Eloquent\Builder|ImplodeImg simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|ImplodeImg whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|ImplodeImg whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|ImplodeImg whereLike($column, $value, $boolean = 'and')
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Implodeimg',
    description: 'ImplodeImg model'
)]
class ImplodeImg extends Model
{

    use HasFactory;
    use Filterable;

    protected $table = 'implode_img';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'company_id' => 'int',
        'product_id' => 'int'
    ];

    protected $fillable = [
        'company_id',
        'product_id',
        'imploded'
    ];
}
