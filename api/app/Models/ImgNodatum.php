<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ImgNodatum
 *
 * @property int|null $pro_id
 * @property int|null $product_id
 * @property string|null $sku
 * @property int|null $company_id
 * @package App\Models
 * @method static \Database\Factories\ImgNodatumFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|ImgNodatum filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|ImgNodatum newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ImgNodatum newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ImgNodatum paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|ImgNodatum query()
 * @method static \Illuminate\Database\Eloquent\Builder|ImgNodatum simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|ImgNodatum whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|ImgNodatum whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ImgNodatum whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|ImgNodatum whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|ImgNodatum whereProId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ImgNodatum whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ImgNodatum whereSku($value)
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Imgnodatum',
    description: 'ImgNodatum model'
)]
class ImgNodatum extends Model
{

    use HasFactory;
    use Filterable;

    protected $table = 'img_nodata';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'pro_id' => 'int',
        'product_id' => 'int',
        'company_id' => 'int'
    ];

    protected $fillable = [
        'pro_id',
        'product_id',
        'sku',
        'company_id'
    ];
}
