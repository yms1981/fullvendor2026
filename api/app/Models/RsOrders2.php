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
 * Class RsOrders2
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
 * @property string|null $unit_type
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
 * @property string|null $images
 * @property int|null $cImg
 * @property string|null $implode_img
 * @package App\Models
 * @method static \Illuminate\Database\Eloquent\Builder|RsOrders2 filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|RsOrders2 newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RsOrders2 newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RsOrders2 paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|RsOrders2 query()
 * @method static \Illuminate\Database\Eloquent\Builder|RsOrders2 simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|RsOrders2 whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|RsOrders2 whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|RsOrders2 whereLike($column, $value, $boolean = 'and')
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Rsorders2',
    description: 'RsOrders2 model'
)]
class RsOrders2 extends Model
{

    use HasFactory;
    use Filterable;

    protected $table = 'rs_orders2';
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
        'cImg' => 'int'
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
        'unit_type',
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
        'images',
        'cImg',
        'implode_img'
    ];
}
