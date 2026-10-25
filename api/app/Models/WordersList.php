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
 * Class WordersList
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
 * @property Carbon|null $updated
 * @property string|null $approved_by
 * @property string|null $delivery_notes
 * @property int|null $warehouse_user_id
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
 * @property string|null $name_status_spanish
 * @property string|null $name_status_english
 * @property string|null $color
 * @property string|null $warehouse_name
 * @property Company|null $company
 * @property Customer $customer
 * @property Language $language
 * @property Order $order
 * @property User|null $user
 * @property WarehouseUser|null $warehouse_user
 * @package App\Models
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @method static \Database\Factories\WordersListFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|WordersList filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|WordersList newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WordersList newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WordersList onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|WordersList paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|WordersList query()
 * @method static \Illuminate\Database\Eloquent\Builder|WordersList simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|WordersList whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WordersList whereAmountDelivered($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WordersList whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WordersList whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|WordersList whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WordersList whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WordersList whereCreated($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WordersList whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WordersList whereDateConfirmed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WordersList whereDateExpiredEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WordersList whereDateRejectedEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WordersList whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WordersList whereDeliveryNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WordersList whereDeliveryStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WordersList whereDiscount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WordersList whereDiscountA($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WordersList whereDiscountDelivered($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WordersList whereDiscountType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WordersList whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|WordersList whereExpirationDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WordersList whereInternalNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WordersList whereLanguageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WordersList whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|WordersList whereNameStatusEnglish($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WordersList whereNameStatusSpanish($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WordersList whereOrderComments($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WordersList whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WordersList whereOrderNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WordersList whereOrderStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WordersList wherePaymentMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WordersList wherePaymentStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WordersList whereSendEmailOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WordersList whereSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WordersList whereTipoD($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WordersList whereTokenPass($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WordersList whereTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WordersList whereTotalDelivered($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WordersList whereTransactionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WordersList whereUpdated($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WordersList whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WordersList whereWarehouseAssignDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WordersList whereWarehouseName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WordersList whereWarehouseUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WordersList withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|WordersList withoutTrashed()
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Worderslist',
    description: 'WordersList model'
)]
class WordersList extends Model
{

    use HasFactory;
    use Filterable;

    use SoftDeletes;

    const CREATED_AT = 'created';
    const UPDATED_AT = 'updated';
    protected $table = 'wordersList';
    protected $primaryKey = 'order_id';
    public $incrementing = false;


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
        'warehouse_id' => 'int',
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
        'name_status_spanish',
        'name_status_english',
        'color',
        'warehouse_name'
    ];

	public function company()
	{
		return $this->belongsTo(Company::class);
	}

	public function customer()
	{
		return $this->belongsTo(Customer::class);
	}

	public function language()
	{
		return $this->belongsTo(Language::class);
	}

	public function order()
	{
		return $this->belongsTo(Order::class);
	}

	public function user()
	{
		return $this->belongsTo(User::class);
	}

	public function warehouse_user()
	{
		return $this->belongsTo(WarehouseUser::class);
	}
}
