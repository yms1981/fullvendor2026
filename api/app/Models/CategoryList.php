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
 * Class CategoryList
 *
 * @property int|null $order_id
 * @property int|null $cat_id
 * @property int|null $category_id
 * @property int|null $language_id
 * @property int|null $company_id
 * @property string|null $category_name
 * @property string|null $images
 * @property int|null $category_status
 * @property Carbon|null $category_created_at
 * @property int|null $id_kor
 * @property int|null $category_default
 * @property int $items
 * @package App\Models
 * @method static \Database\Factories\CategoryListFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|CategoryList filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|CategoryList newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CategoryList newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CategoryList paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|CategoryList query()
 * @method static \Illuminate\Database\Eloquent\Builder|CategoryList simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|CategoryList whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|CategoryList whereCatId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CategoryList whereCategoryCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CategoryList whereCategoryDefault($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CategoryList whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CategoryList whereCategoryName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CategoryList whereCategoryStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CategoryList whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CategoryList whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|CategoryList whereIdKor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CategoryList whereImages($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CategoryList whereItems($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CategoryList whereLanguageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CategoryList whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|CategoryList whereOrderId($value)
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Categorylist',
    description: 'CategoryList model'
)]
class CategoryList extends Model
{

    use HasFactory;
    use Filterable;

    protected $table = 'categoryList';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'order_id' => 'int',
        'cat_id' => 'int',
        'category_id' => 'int',
        'language_id' => 'int',
        'company_id' => 'int',
        'category_status' => 'int',
        'category_created_at' => 'datetime',
        'id_kor' => 'int',
        'category_default' => 'int',
        'items' => 'int'
    ];

    protected $fillable = [
        'order_id',
        'cat_id',
        'category_id',
        'language_id',
        'company_id',
        'category_name',
        'images',
        'category_status',
        'category_created_at',
        'id_kor',
        'category_default',
        'items'
    ];
}
