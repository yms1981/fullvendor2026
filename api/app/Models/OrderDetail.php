<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use App\Enums\OrderStatusCodes;
use Carbon\Carbon;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class OrderDetail
 *
 * @property int $detail_id
 * @property int $order_id
 * @property int $product_id
 * @property float $qty
 * @property float $delivered_quantity
 * @property float $discount
 * @property int $discount_type
 * @property float $sale_price
 * @property float $fob_price
 * @property float $sale_price_app
 * @property float $purchase_price
 * @property string $comment
 * @property Carbon $created
 * @property int $id_kor
 * @property string $comments
 * @property Carbon|null $modify_date
 * @property Carbon|null $fixed_pricedate
 * @property float|null $amount_sales
 * @property float|null $discount_amount
 * @property float|null $total_amount
 * @property float|null $amount_delivered
 * @property float|null $discount_delivered
 * @property float|null $total_delivered
 * @property float|null $amount
 * @property float|null $discountt
 * @property Carbon|null $delivered_date
 * @property float|null $total
 * @property string|null $groupcustomer
 * @property string|null $tipolista
 * @property float|null $perc_price
 * @property float|null $salesp
 * @property float|null $impprice
 * @property float|null $totalprice
 * @property float|null $import_price
 * @property float|null $pack
 * @property float|null $delivered_pack
 * @property int|null $company_id
 * @property Product $product
 * @package App\Models
 * @property string|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Order $order
 * @method static \Database\Factories\OrderDetailFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDetail filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDetail newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDetail newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDetail onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDetail paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDetail query()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDetail simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDetail whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDetail whereAmountDelivered($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDetail whereAmountSales($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDetail whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDetail whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDetail whereComments($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDetail whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDetail whereCreated($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDetail whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDetail whereDeliveredDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDetail whereDeliveredPack($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDetail whereDeliveredQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDetail whereDetailId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDetail whereDiscount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDetail whereDiscountAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDetail whereDiscountDelivered($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDetail whereDiscountType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDetail whereDiscountt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDetail whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDetail whereFixedPricedate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDetail whereFobPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDetail whereGroupcustomer($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDetail whereIdKor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDetail whereImportPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDetail whereImpprice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDetail whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDetail whereModifyDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDetail whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDetail wherePack($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDetail wherePercPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDetail whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDetail wherePurchasePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDetail whereQty($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDetail whereSalePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDetail whereSalePriceApp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDetail whereSalesp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDetail whereTipolista($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDetail whereTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDetail whereTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDetail whereTotalDelivered($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDetail whereTotalprice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDetail whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDetail withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderDetail withoutTrashed()
 * @property-read bool $star
 * @property-read float $total_amount_assigned
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Orderdetail',
    description: 'OrderDetail model'
)]
class OrderDetail extends Model
{

    use HasFactory;
    use Filterable;

    use SoftDeletes;

    const CREATED_AT = 'created';
    const UPDATED_AT = 'modify_date';
    protected $table = 'order_details';
    protected $primaryKey = 'detail_id';


    protected $casts = [
        'order_id' => 'int',
        'product_id' => 'int',
        'qty' => 'float',
        'delivered_quantity' => 'float',
        'discount' => 'float',
        'discount_type' => 'int',
        'sale_price' => 'float',
        'fob_price' => 'float',
        'sale_price_app' => 'float',
        'purchase_price' => 'float',
        'created' => 'datetime',
        'id_kor' => 'int',
        'modify_date' => 'datetime',
        'fixed_pricedate' => 'datetime',
        'amount_sales' => 'float',
        'discount_amount' => 'float',
        'total_amount' => 'float',
        'amount_delivered' => 'float',
        'discount_delivered' => 'float',
        'total_delivered' => 'float',
        'amount' => 'float',
        'discountt' => 'float',
        'delivered_date' => 'datetime',
        'total' => 'float',
        'perc_price' => 'float',
        'salesp' => 'float',
        'impprice' => 'float',
        'totalprice' => 'float',
        'import_price' => 'float',
        'pack' => 'float',
        'delivered_pack' => 'float',
        'company_id' => 'int'
    ];

    protected static function booted(): void
    {
        static::saving(function (OrderDetail $detail) {
            // Órdenes tipo_d = D: qty negativa → ABS(qty) antes de persistir.
            app(\App\Services\Orders\EnsurePositiveQtyForDocumentOrdersService::class)
                ->normalizeDetailQty($detail);
        });
    }

    protected $fillable = [
        'order_id',
        'product_id',
        'qty',
        'delivered_quantity',
        'discount',
        'discount_type',
        'sale_price',
        'fob_price',
        'sale_price_app',
        'purchase_price',
        'comment',
        'created',
        'id_kor',
        'comments',
        'fixed_pricedate',
        'amount_sales',
        'discount_amount',
        'total_amount',
        'amount_delivered',
        'discount_delivered',
        'total_delivered',
        'amount',
        'discountt',
        'delivered_date',
        'total',
        'groupcustomer',
        'tipolista',
        'perc_price',
        'salesp',
        'impprice',
        'totalprice',
        'import_price',
        'pack',
        'delivered_pack',
        'company_id'
    ];

    protected $appends = [
        'total_amount_assigned'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }

	public function product()
	{
		return $this->belongsTo(Product::class, 'product_id', 'pro_id');
	}

    public function getTotalAmountAssignedAttribute(): float
    {
        if ($this->delivered_quantity > 0) {
            return $this->delivered_quantity * $this->sale_price;
        }
        return 0;
    }

    public function getStarAttribute(): bool
    {
        $customerId = $this->order->customer_id ?? null;

        if (!$customerId || !$this->product) {
            return false;
        }

        $this->unsetRelation('order');

        return $this->product
            ->order_details()
            ->whereHas('order', function ($q) use ($customerId) {
                $q->where('customer_id', $customerId)
                    ->whereIn('status_id', OrderStatusCodes::forStar());
            })
            ->exists();
    }
}
