<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use App\Enums\OrderStatusCodes;
use Carbon\Carbon;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class CatalogCart
 *
 * @package App\Models
 * @property string|null $tipo_d
 * @property int $order_id
 * @property string|null $UUID
 * @property string|null $order_number
 * @property int|null $company_id
 * @property int|null $user_id
 * @property int $language_id
 * @property int $customer_id
 * @property int $payment_method 0-CashOn Delivery, 1-Payment Gateway
 * @property int $payment_status 0-Due, 1-Paid, 2-Refund, 3-Settled
 * @property string|null $transaction_id
 * @property string|null $order_comments
 * @property int|null $delivery_status 1=Full Delivered, 0=Partial Delivered
 * @property float $discount
 * @property int $discount_type 1 = in %, 2 = in $
 * @property \Illuminate\Support\Carbon|null $created
 * @property \Illuminate\Support\Carbon $updated
 * @property string|null $approved_by
 * @property string|null $delivery_notes
 * @property \Illuminate\Support\Carbon|null $warehouse_assign_date
 * @property string $source
 * @property string $internal_notes
 * @property string|null $token_pass
 * @property \Illuminate\Support\Carbon|null $date_confirmed
 * @property \Illuminate\Support\Carbon|null $send_email_order
 * @property \Illuminate\Support\Carbon|null $date_rejected_email
 * @property \Illuminate\Support\Carbon|null $date_expired_email
 * @property int|null $expiration_days
 * @property float|null $amount
 * @property float|null $discount_a
 * @property float|null $total_amount
 * @property float|null $amount_delivered
 * @property float|null $discount_delivered
 * @property float|null $total_delivered
 * @property int|null $portal_vendor
 * @property \Illuminate\Support\Carbon|null $virified_byportal
 * @property int|null $id_kor
 * @property int|null $cart_id
 * @property int|null $catalog_id
 * @property int|null $update_bybackend
 * @property int|null $discount_type_since_movil
 * @property float|null $discount_movil
 * @property int|null $leido
 * @property int|null $proforma
 * @property string $new_address
 * @property array|null $new_address_json
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int $status_id
 * @property-read \App\Models\Catalog|null $catalog
 * @property-read CatalogCart|null $catalog_cart
 * @property-read Collection<int, CatalogCart> $catalog_carts
 * @property-read int|null $catalog_carts_count
 * @property-read \App\Models\Company|null $company
 * @property-read \App\Models\Customer $customer
 * @property-read Collection<int, \App\Models\DeliveredOrder> $delivered_orders
 * @property-read int|null $delivered_orders_count
 * @property-read \App\Models\Language|null $language
 * @property-read Collection<int, \App\Models\OrderDetail> $order_details
 * @property-read int|null $order_details_count
 * @property-read Collection<int, \App\Models\OrderStatusHistory> $order_status_histories
 * @property-read int|null $order_status_histories_count
 * @property-read \App\Models\Vendor|null $seller
 * @property-read \App\Models\StatusOrder $statusOrder
 * @property-read \App\Models\WarehouseUser|null $warehouse_user
 * @property-read \App\Models\WordersList|null $worders_list
 * @method static \Database\Factories\CatalogCartFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCart filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCart newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCart newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCart onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCart paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCart query()
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCart simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCart whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCart whereAmountDelivered($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCart whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCart whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCart whereCartId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCart whereCatalogId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCart whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCart whereCreated($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCart whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCart whereDateConfirmed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCart whereDateExpiredEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCart whereDateRejectedEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCart whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCart whereDeliveryNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCart whereDeliveryStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCart whereDiscount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCart whereDiscountA($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCart whereDiscountDelivered($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCart whereDiscountMovil($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCart whereDiscountType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCart whereDiscountTypeSinceMovil($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCart whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCart whereExpirationDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCart whereIdKor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCart whereInternalNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCart whereLanguageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCart whereLeido($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCart whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCart whereNewAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCart whereNewAddressJson($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCart whereOrderComments($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCart whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCart whereOrderNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCart wherePaymentMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCart wherePaymentStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCart wherePortalVendor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCart whereProforma($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCart whereSendEmailOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCart whereSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCart whereStatusId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCart whereTipoD($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCart whereTokenPass($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCart whereTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCart whereTotalDelivered($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCart whereTransactionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCart whereUUID($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCart whereUpdateBybackend($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCart whereUpdated($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCart whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCart whereVirifiedByportal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCart whereWarehouseAssignDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCart whereWarehouseUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCart withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCart withoutTrashed()
 * @property int|null $address_id
 * @property-read \App\Models\Address|null $address
 * @property-read mixed $descuento
 * @property-read mixed $descuento_asignado
 * @property-read Collection<int, \App\Models\OrderNote> $notes
 * @property-read int|null $notes_count
 * @property-read mixed $subtotal
 * @property-read mixed $subtotal_asignado
 * @property-read mixed $total
 * @property-read mixed $total_asignado
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CatalogCart whereAddressId($value)
 * @mixin \Eloquent
 */
class CatalogCart extends Order
{

    use HasFactory;
    use Filterable;
    use SoftDeletes;

    public function save(array $options = [])
    {
        //Ensuring that keeps being a Shopping Cart
        $options['status_id'] = StatusOrder::getIdByCode(OrderStatusCodes::SHOPPING);
        return parent::save($options);
    }

}
