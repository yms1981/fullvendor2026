<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class OrdersCategory
 *
 * @property int $order_category_id
 * @property int|null $category_id
 * @property int|null $catalog_id
 * @property int|null $company_id
 * @property int|null $order
 * @property string $catalog_name
 * @package App\Models
 * @method static \Database\Factories\OrdersCategoryFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|OrdersCategory filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|OrdersCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OrdersCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OrdersCategory paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|OrdersCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder|OrdersCategory simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|OrdersCategory whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|OrdersCategory whereCatalogId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrdersCategory whereCatalogName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrdersCategory whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrdersCategory whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrdersCategory whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|OrdersCategory whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|OrdersCategory whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrdersCategory whereOrderCategoryId($value)
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Orderscategory',
    description: 'OrdersCategory model'
)]
class OrdersCategory extends Model
{

    use HasFactory;
    use Filterable;

    protected $table = 'orders_categories';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'order_category_id' => 'int',
        'category_id' => 'int',
        'catalog_id' => 'int',
        'company_id' => 'int',
        'order' => 'int'
    ];

    protected $fillable = [
        'order_category_id',
        'category_id',
        'catalog_id',
        'company_id',
        'order',
        'catalog_name'
    ];
}
