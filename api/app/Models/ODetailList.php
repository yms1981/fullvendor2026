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
 * Class ODetailList
 *
 * @property int $detail_id
 * @property int $order_id
 * @property int $product_id
 * @property string|null $sku
 * @property string|null $name
 * @property float $qty
 * @property float $delivered_quantity
 * @property float $discount
 * @property int $discount_type
 * @property float $sale_price
 * @property float $fob_price
 * @property float $purchase_price
 * @property string $comment
 * @property Carbon $created
 * @property float|null $amount_sales
 * @property float|null $discount_amount
 * @property float|null $total_amount
 * @property float|null $amount_delivered
 * @property float|null $discount_delivered
 * @property float|null $total_delivered
 * @package App\Models
 * @property int|null $company_id
 * @property string|null $modify_date
 * @property float $delivered_pack
 * @property float|null $pack
 * @method static \Database\Factories\ODetailListFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|ODetailList filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|ODetailList newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ODetailList newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ODetailList paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|ODetailList query()
 * @method static \Illuminate\Database\Eloquent\Builder|ODetailList simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|ODetailList whereAmountDelivered($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ODetailList whereAmountSales($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ODetailList whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|ODetailList whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ODetailList whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ODetailList whereCreated($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ODetailList whereDeliveredPack($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ODetailList whereDeliveredQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ODetailList whereDetailId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ODetailList whereDiscount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ODetailList whereDiscountAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ODetailList whereDiscountDelivered($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ODetailList whereDiscountType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ODetailList whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|ODetailList whereFobPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ODetailList whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|ODetailList whereModifyDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ODetailList whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ODetailList whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ODetailList wherePack($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ODetailList whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ODetailList wherePurchasePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ODetailList whereQty($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ODetailList whereSalePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ODetailList whereSku($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ODetailList whereTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ODetailList whereTotalDelivered($value)
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Odetaillist',
    description: 'ODetailList model'
)]
class ODetailList extends Model
{

    use HasFactory;
    use Filterable;

    protected $table = 'oDetailList';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'detail_id' => 'int',
        'order_id' => 'int',
        'product_id' => 'int',
        'qty' => 'float',
        'delivered_quantity' => 'float',
        'discount' => 'float',
        'discount_type' => 'int',
        'sale_price' => 'float',
        'fob_price' => 'float',
        'purchase_price' => 'float',
        'created' => 'datetime',
        'amount_sales' => 'float',
        'discount_amount' => 'float',
        'total_amount' => 'float',
        'amount_delivered' => 'float',
        'discount_delivered' => 'float',
        'total_delivered' => 'float'
    ];

    protected $fillable = [
        'detail_id',
        'order_id',
        'product_id',
        'sku',
        'name',
        'qty',
        'delivered_quantity',
        'discount',
        'discount_type',
        'sale_price',
        'fob_price',
        'purchase_price',
        'comment',
        'created',
        'amount_sales',
        'discount_amount',
        'total_amount',
        'amount_delivered',
        'discount_delivered',
        'total_delivered'
    ];
}
