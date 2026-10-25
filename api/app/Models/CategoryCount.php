<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class CategoryCount
 *
 * @property int|null $company_id
 * @property int $items
 * @property string|null $category_value
 * @package App\Models
 * @method static \Database\Factories\CategoryCountFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|CategoryCount filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|CategoryCount newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CategoryCount newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CategoryCount paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|CategoryCount query()
 * @method static \Illuminate\Database\Eloquent\Builder|CategoryCount simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|CategoryCount whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|CategoryCount whereCategoryValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CategoryCount whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CategoryCount whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|CategoryCount whereItems($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CategoryCount whereLike($column, $value, $boolean = 'and')
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Categorycount',
    description: 'CategoryCount model'
)]
class CategoryCount extends Model
{

    use HasFactory;
    use Filterable;

    protected $table = 'category_count';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'company_id' => 'int',
        'items' => 'int'
    ];

    protected $fillable = [
        'company_id',
        'items',
        'category_value'
    ];
}
