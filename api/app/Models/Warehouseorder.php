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
 * Class Warehouseorder
 *
 * @property int|null $ano_id
 * @property int|null $mes_id
 * @property string|null $tipo_d
 * @property int $order_id
 * @property string|null $order_number
 * @property int|null $company_id
 * @property int|null $user_id
 * @property int $language_id
 * @property int $customer_id
 * @property int $payment_method
 * @property int $payment_status
 * @property string|null $transaction_id
 * @property string|null $order_comments
 * @property int $order_status
 * @property int|null $delivery_status
 * @property float $discount
 * @property int $discount_type
 * @property Carbon|null $created
 * @property Carbon $updated
 * @property string|null $approved_by
 * @property string|null $delivery_notes
 * @property int|null $warehouse_user_id
 * @property Carbon|null $warehouse_assign_date
 * @property string $source
 * @property string $internal_notes
 * @property string|null $token_pass
 * @property int|null $expiration_days
 * @property float|null $amount
 * @property float|null $discount_a
 * @property float|null $total_amount
 * @property float|null $amount_delivered
 * @property float|null $discount_delivered
 * @property float|null $total_delivered
 * @property string|null $name_status_spanish
 * @property string|null $name_status_english
 * @property string|null $color
 * @property string|null $warehouse_name
 * @package App\Models
 * @method static \Illuminate\Database\Eloquent\Builder|Warehouseorder filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Warehouseorder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Warehouseorder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Warehouseorder paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Warehouseorder query()
 * @method static \Illuminate\Database\Eloquent\Builder|Warehouseorder simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Warehouseorder whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Warehouseorder whereAmountDelivered($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Warehouseorder whereAnoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Warehouseorder whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Warehouseorder whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Warehouseorder whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Warehouseorder whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Warehouseorder whereCreated($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Warehouseorder whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Warehouseorder whereDeliveryNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Warehouseorder whereDeliveryStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Warehouseorder whereDiscount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Warehouseorder whereDiscountA($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Warehouseorder whereDiscountDelivered($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Warehouseorder whereDiscountType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Warehouseorder whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Warehouseorder whereExpirationDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Warehouseorder whereInternalNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Warehouseorder whereLanguageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Warehouseorder whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Warehouseorder whereMesId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Warehouseorder whereNameStatusEnglish($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Warehouseorder whereNameStatusSpanish($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Warehouseorder whereOrderComments($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Warehouseorder whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Warehouseorder whereOrderNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Warehouseorder whereOrderStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Warehouseorder wherePaymentMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Warehouseorder wherePaymentStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Warehouseorder whereSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Warehouseorder whereTipoD($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Warehouseorder whereTokenPass($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Warehouseorder whereTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Warehouseorder whereTotalDelivered($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Warehouseorder whereTransactionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Warehouseorder whereUpdated($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Warehouseorder whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Warehouseorder whereWarehouseAssignDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Warehouseorder whereWarehouseName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Warehouseorder whereWarehouseUserId($value)
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Warehouseorder',
    description: 'Warehouseorder model'
)]
class Warehouseorder extends Model
{

    use HasFactory;
    use Filterable;

    protected $table = 'warehouseorders';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'ano_id' => 'int',
        'mes_id' => 'int',
        'order_id' => 'int',
        'company_id' => 'int',
        'user_id' => 'int',
        'language_id' => 'int',
        'customer_id' => 'int',
        'payment_method' => 'int',
        'payment_status' => 'int',
        'order_status' => 'int',
        'delivery_status' => 'int',
        'discount' => 'float',
        'discount_type' => 'int',
        'created' => 'datetime',
        'updated' => 'datetime',
        'warehouse_user_id' => 'int',
        'warehouse_assign_date' => 'datetime',
        'expiration_days' => 'int',
        'amount' => 'float',
        'discount_a' => 'float',
        'total_amount' => 'float',
        'amount_delivered' => 'float',
        'discount_delivered' => 'float',
        'total_delivered' => 'float'
    ];

    protected $fillable = [
        'ano_id',
        'mes_id',
        'tipo_d',
        'order_id',
        'order_number',
        'company_id',
        'user_id',
        'language_id',
        'customer_id',
        'payment_method',
        'payment_status',
        'transaction_id',
        'order_comments',
        'order_status',
        'delivery_status',
        'discount',
        'discount_type',
        'created',
        'updated',
        'approved_by',
        'delivery_notes',
        'warehouse_user_id',
        'warehouse_assign_date',
        'source',
        'internal_notes',
        'token_pass',
        'expiration_days',
        'amount',
        'discount_a',
        'total_amount',
        'amount_delivered',
        'discount_delivered',
        'total_delivered',
        'name_status_spanish',
        'name_status_english',
        'color',
        'warehouse_name'
    ];
}
