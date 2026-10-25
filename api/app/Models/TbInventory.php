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
 * Class TbInventory
 *
 * @property int $company_id
 * @property int $product_id
 * @property string|null $sku
 * @property string|null $name
 * @property string|null $descriptions
 * @property float|null $qty
 * @property Carbon|null $modify_date
 * @property float|null $sp
 * @property float|null $pp
 * @property float $sale_price
 * @property float $purchase_price
 * @property string|null $category_id
 * @package App\Models
 * @method static \Illuminate\Database\Eloquent\Builder|TbInventory filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|TbInventory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TbInventory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TbInventory paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|TbInventory query()
 * @method static \Illuminate\Database\Eloquent\Builder|TbInventory simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|TbInventory whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|TbInventory whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TbInventory whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TbInventory whereDescriptions($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TbInventory whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|TbInventory whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|TbInventory whereModifyDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TbInventory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TbInventory wherePp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TbInventory whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TbInventory wherePurchasePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TbInventory whereQty($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TbInventory whereSalePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TbInventory whereSku($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TbInventory whereSp($value)
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Tbinventory',
    description: 'TbInventory model'
)]
class TbInventory extends Model
{

    use HasFactory;
    use Filterable;

    protected $table = 'tb_inventory';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'company_id' => 'int',
        'product_id' => 'int',
        'qty' => 'float',
        'modify_date' => 'datetime',
        'sp' => 'float',
        'pp' => 'float',
        'sale_price' => 'float',
        'purchase_price' => 'float'
    ];

    protected $fillable = [
        'company_id',
        'product_id',
        'sku',
        'name',
        'descriptions',
        'qty',
        'modify_date',
        'sp',
        'pp',
        'sale_price',
        'purchase_price',
        'category_id'
    ];
}
