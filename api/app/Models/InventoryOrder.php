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
 * Class InventoryOrder
 *
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
 * @property Carbon|null $warehouse_assign_date
 * @property string $source
 * @property string $internal_notes
 * @property string|null $token_pass
 * @property Carbon|null $date_confirmed
 * @property Carbon|null $send_email_order
 * @property Carbon|null $date_rejected_email
 * @property Carbon|null $date_expired_email
 * @property int|null $expiration_days
 * @property float|null $amount
 * @property float|null $discount_a
 * @property float|null $total_amount
 * @property float|null $amount_delivered
 * @property float|null $discount_delivered
 * @property float|null $total_delivered
 * @property string|null $business_name
 * @property string|null $name
 * @property string|null $name_status_spanish
 * @property string|null $name_status_english
 * @property string|null $color
 * @package App\Models
 * @method static \Database\Factories\InventoryOrderFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|InventoryOrder filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|InventoryOrder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|InventoryOrder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|InventoryOrder paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|InventoryOrder query()
 * @method static \Illuminate\Database\Eloquent\Builder|InventoryOrder simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|InventoryOrder whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InventoryOrder whereAmountDelivered($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InventoryOrder whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InventoryOrder whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|InventoryOrder whereBusinessName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InventoryOrder whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InventoryOrder whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InventoryOrder whereCreated($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InventoryOrder whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InventoryOrder whereDateConfirmed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InventoryOrder whereDateExpiredEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InventoryOrder whereDateRejectedEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InventoryOrder whereDeliveryNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InventoryOrder whereDeliveryStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InventoryOrder whereDiscount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InventoryOrder whereDiscountA($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InventoryOrder whereDiscountDelivered($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InventoryOrder whereDiscountType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InventoryOrder whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|InventoryOrder whereExpirationDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InventoryOrder whereInternalNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InventoryOrder whereLanguageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InventoryOrder whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|InventoryOrder whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InventoryOrder whereNameStatusEnglish($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InventoryOrder whereNameStatusSpanish($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InventoryOrder whereOrderComments($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InventoryOrder whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InventoryOrder whereOrderNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InventoryOrder whereOrderStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InventoryOrder wherePaymentMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InventoryOrder wherePaymentStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InventoryOrder whereSendEmailOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InventoryOrder whereSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InventoryOrder whereTipoD($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InventoryOrder whereTokenPass($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InventoryOrder whereTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InventoryOrder whereTotalDelivered($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InventoryOrder whereTransactionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InventoryOrder whereUpdated($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InventoryOrder whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InventoryOrder whereWarehouseAssignDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InventoryOrder whereWarehouseUserId($value)
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Inventoryorder',
    description: 'InventoryOrder model'
)]
class InventoryOrder extends Model
{

    use HasFactory;
    use Filterable;

    protected $table = 'inventory_orders';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
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
        'date_confirmed' => 'datetime',
        'send_email_order' => 'datetime',
        'date_rejected_email' => 'datetime',
        'date_expired_email' => 'datetime',
        'expiration_days' => 'int',
        'amount' => 'float',
        'discount_a' => 'float',
        'total_amount' => 'float',
        'amount_delivered' => 'float',
        'discount_delivered' => 'float',
        'total_delivered' => 'float'
    ];

    protected $fillable = [
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
        'date_confirmed',
        'send_email_order',
        'date_rejected_email',
        'date_expired_email',
        'expiration_days',
        'amount',
        'discount_a',
        'total_amount',
        'amount_delivered',
        'discount_delivered',
        'total_delivered',
        'business_name',
        'name',
        'name_status_spanish',
        'name_status_english',
        'color'
    ];
}
