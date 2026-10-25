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
 * Class Myorder
 *
 * @property Carbon|null $fechaorden
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
 * @property int|null $portal_vendor
 * @property Carbon|null $virified_byportal
 * @property int|null $id_kor
 * @property int|null $cart_id
 * @property int|null $catalog_id
 * @property int|null $update_bybackend
 * @property int|null $discount_type_since_movil
 * @property float|null $discount_movil
 * @property int|null $leido
 * @property array|null $new_address_json
 * @package App\Models
 * @method static \Database\Factories\MyorderFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Myorder filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Myorder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Myorder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Myorder paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Myorder query()
 * @method static \Illuminate\Database\Eloquent\Builder|Myorder simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Myorder whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Myorder whereAmountDelivered($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Myorder whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Myorder whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Myorder whereCartId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Myorder whereCatalogId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Myorder whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Myorder whereCreated($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Myorder whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Myorder whereDateConfirmed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Myorder whereDateExpiredEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Myorder whereDateRejectedEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Myorder whereDeliveryNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Myorder whereDeliveryStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Myorder whereDiscount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Myorder whereDiscountA($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Myorder whereDiscountDelivered($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Myorder whereDiscountMovil($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Myorder whereDiscountType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Myorder whereDiscountTypeSinceMovil($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Myorder whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Myorder whereExpirationDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Myorder whereFechaorden($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Myorder whereIdKor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Myorder whereInternalNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Myorder whereLanguageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Myorder whereLeido($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Myorder whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Myorder whereOrderComments($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Myorder whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Myorder whereOrderNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Myorder whereOrderStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Myorder wherePaymentMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Myorder wherePaymentStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Myorder wherePortalVendor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Myorder whereSendEmailOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Myorder whereSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Myorder whereTipoD($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Myorder whereTokenPass($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Myorder whereTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Myorder whereTotalDelivered($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Myorder whereTransactionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Myorder whereUpdateBybackend($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Myorder whereUpdated($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Myorder whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Myorder whereVirifiedByportal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Myorder whereWarehouseAssignDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Myorder whereWarehouseUserId($value)
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Myorder',
    description: 'Myorder model'
)]
class Myorder extends Model
{

    use HasFactory;
    use Filterable;

    protected $table = 'myorders';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'fechaorden' => 'datetime',
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
        'total_delivered' => 'float',
        'portal_vendor' => 'int',
        'virified_byportal' => 'datetime',
        'id_kor' => 'int',
        'cart_id' => 'int',
        'catalog_id' => 'int',
        'update_bybackend' => 'int',
        'discount_type_since_movil' => 'int',
        'discount_movil' => 'float',
        'leido' => 'int',
    'new_address_json' => 'json'
	];

    protected $fillable = [
        'fechaorden',
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
        'portal_vendor',
        'virified_byportal',
        'id_kor',
        'cart_id',
        'catalog_id',
        'update_bybackend',
        'discount_type_since_movil',
        'discount_movil',
        'leido',
    'new_address_json'
	];
}
