<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Inventory
 *
 * @property int $company_id
 * @property int $product_id
 * @property int|null $kor_id
 * @property float|null $qty
 * @property float|null $sale_price
 * @property float|null $purchased_price
 * @property Carbon|null $modify_date
 * @property Company $company
 * @property Product $product
 * @package App\Models
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @method static \Database\Factories\InventoryFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Inventory filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Inventory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Inventory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Inventory onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Inventory paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Inventory query()
 * @method static \Illuminate\Database\Eloquent\Builder|Inventory simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Inventory whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Inventory whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Inventory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Inventory whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Inventory whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Inventory whereKorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Inventory whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Inventory whereModifyDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Inventory whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Inventory wherePurchasedPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Inventory whereQty($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Inventory whereSalePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Inventory withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Inventory withoutTrashed()
 * @mixin \Eloquent
 */
class Inventory extends Pivot
{

    use HasFactory;
    use Filterable;

    use SoftDeletes;

    const UPDATED_AT = 'modify_date';
    protected $table = 'inventory';
    public $incrementing = false;

    protected $foreignKey = 'company_id';
    protected $relatedKey = 'product_id';


    protected $casts = [
        'company_id' => 'int',
        'product_id' => 'int',
        'kor_id' => 'int',
        'qty' => 'float',
        'sale_price' => 'float',
        'purchased_price' => 'float',
        'modify_date' => 'datetime'
    ];

    protected $fillable = [
        'kor_id',
        'qty',
        'sale_price',
        'purchased_price',
        'modify_date'
    ];

	public function company()
	{
		return $this->belongsTo(Company::class);
	}

	public function product()
	{
		return $this->belongsTo(Product::class);
	}
}
