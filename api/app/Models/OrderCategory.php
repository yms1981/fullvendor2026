<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class OrderCategory
 *
 * @property int $order_category_id
 * @property int $category_id
 * @property int $catalog_id
 * @property int|null $company_id
 * @property int|null $order
 * @property Carbon|null $created_at
 * @property Catalog $catalog
 * @property Category $category
 * @property Company|null $company
 * @package App\Models
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @method static \Database\Factories\OrderCategoryFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|OrderCategory filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderCategory onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderCategory paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderCategory simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderCategory whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|OrderCategory whereCatalogId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderCategory whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderCategory whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderCategory whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderCategory whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|OrderCategory whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|OrderCategory whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderCategory whereOrderCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderCategory whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderCategory withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderCategory withoutTrashed()
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Ordercategory',
    description: 'OrderCategory model'
)]
class OrderCategory extends Model
{

    use HasFactory;
    use Filterable;

    use SoftDeletes;protected $table = 'order_categories';
    protected $primaryKey = 'order_category_id';


    protected $casts = [
        'category_id' => 'int',
        'catalog_id' => 'int',
        'company_id' => 'int',
        'order' => 'int'
    ];

    protected $fillable = [
        'category_id',
        'catalog_id',
        'company_id',
        'order'
    ];

	public function catalog()
	{
		return $this->belongsTo(Catalog::class);
	}

	public function category()
	{
		return $this->belongsTo(Category::class);
	}

	public function company()
	{
		return $this->belongsTo(Company::class);
	}
}
