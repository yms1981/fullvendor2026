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
 * Class ProductsListadoActivo
 *
 * @property int $pro_id
 * @property int|null $product_id
 * @property int $language_id
 * @property int|null $company_id
 * @property int|null $warehouse_user_id
 * @property string|null $sku
 * @property string|null $name
 * @property float $sale_price
 * @property string|null $category_id
 * @property string|null $barcode
 * @property string|null $barcode_images
 * @property string|null $tags
 * @property string|null $descriptions
 * @property float $stock
 * @property float $minimum_stock
 * @property int $notify_minimum_stock
 * @property string|null $currency_type
 * @property float $fob_price
 * @property float $purchase_price
 * @property string|null $locations
 * @property string|null $created_by
 * @property Carbon|null $created_on
 * @property Carbon $modified_on
 * @property int $status
 * @property string|null $Img
 * @property string|null $imgURL
 * @property string|null $simgURL
 * @property int|null $id_kor
 * @property string|null $cat_id
 * @property string|null $cats_id
 * @property int|null $sell_with_inventory
 * @package App\Models
 * @method static \Database\Factories\ProductsListadoActivoFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|ProductsListadoActivo filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductsListadoActivo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProductsListadoActivo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProductsListadoActivo paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductsListadoActivo query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProductsListadoActivo simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductsListadoActivo whereBarcode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductsListadoActivo whereBarcodeImages($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductsListadoActivo whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|ProductsListadoActivo whereCatId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductsListadoActivo whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductsListadoActivo whereCatsId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductsListadoActivo whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductsListadoActivo whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductsListadoActivo whereCreatedOn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductsListadoActivo whereCurrencyType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductsListadoActivo whereDescriptions($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductsListadoActivo whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|ProductsListadoActivo whereFobPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductsListadoActivo whereIdKor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductsListadoActivo whereImg($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductsListadoActivo whereImgURL($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductsListadoActivo whereLanguageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductsListadoActivo whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|ProductsListadoActivo whereLocations($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductsListadoActivo whereMinimumStock($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductsListadoActivo whereModifiedOn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductsListadoActivo whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductsListadoActivo whereNotifyMinimumStock($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductsListadoActivo whereProId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductsListadoActivo whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductsListadoActivo wherePurchasePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductsListadoActivo whereSalePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductsListadoActivo whereSellWithInventory($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductsListadoActivo whereSimgURL($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductsListadoActivo whereSku($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductsListadoActivo whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductsListadoActivo whereStock($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductsListadoActivo whereTags($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductsListadoActivo whereUnitType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductsListadoActivo whereWarehouseUserId($value)
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Productslistadoactivo',
    description: 'ProductsListadoActivo model'
)]
class ProductsListadoActivo extends Model
{

    use HasFactory;
    use Filterable;

    protected $table = 'productsListadoActivo';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'pro_id' => 'int',
        'product_id' => 'int',
        'language_id' => 'int',
        'company_id' => 'int',
        'warehouse_user_id' => 'int',
        'sale_price' => 'float',
        'stock' => 'float',
        'minimum_stock' => 'float',
        'notify_minimum_stock' => 'int',
        'fob_price' => 'float',
        'purchase_price' => 'float',
        'created_on' => 'datetime',
        'modified_on' => 'datetime',
        'status' => 'int',
        'id_kor' => 'int',
        'sell_with_inventory' => 'int'
    ];

    protected $fillable = [
        'pro_id',
        'product_id',
        'language_id',
        'company_id',
        'warehouse_user_id',
        'sku',
        'name',
        'sale_price',
        'category_id',
        'barcode',
        'barcode_images',
        'tags',
        'descriptions',
        'stock',
        'minimum_stock',
        'notify_minimum_stock',
        'currency_type',
        'fob_price',
        'purchase_price',
        'locations',
        'created_by',
        'created_on',
        'modified_on',
        'status',
        'Img',
        'imgURL',
        'simgURL',
        'id_kor',
        'cat_id',
        'cats_id',
        'sell_with_inventory'
    ];
}
