<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use App\Enums\OrderStatusCodes;
use Carbon\Carbon;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Class Order
 *
 * @property string|null $tipo_d
 * @property int $order_id
 * @property string|null $order_number
 * @property int|null $company_id
 * @property int|null $seller_id
 * @property int $language_id
 * @property int $customer_id
 * @property int $payment_method
 * @property int $payment_status
 * @property string|null $transaction_id
 * @property string|null $UUID
 * @property int|null $delivery_status
 * @property float $discount
 * @property int $discount_type
 * @property Carbon|null $created
 * @property Carbon $updated
 * @property string|null $approved_by
  * @property Carbon|null $warehouse_assign_date
 * @property string $source
 * @property string|null $token_create
 * @property int|null $user_id_token
 * @property string|null $rol_token
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
 * @property int|null $proforma
 * @property CatalogCart|null $catalog_cart
 * @property Catalog|null $catalog
 * @property Company|null $company
 * @property Customer $customer
 * @property Language $language
 * @property StatusOrder $statusOrder
 * @property WarehouseUser|null $warehouse_user
 * @property Collection|CatalogCart[] $catalog_carts
 * @property Collection|DeliveredOrder[] $delivered_orders
 * @property Collection|OrderStatusHistory[] $order_status_histories
 * @property WordersList $worders_list
 * @property string $uuid
 * @package App\Models
 * @property string|null $deleted_at
 * @property int $status_id
 * @property string|null $order_status Texto del estado traducido (denormalizado desde status_orders según language_id)
 * @property-read int|null $catalog_carts_count
 * @property-read int|null $delivered_orders_count
 * @property-read Collection<int, \App\Models\OrderDetail> $order_details
 * @property-read int|null $order_details_count
 * @property-read int|null $order_status_histories_count
 * @property-read \App\Models\Vendor|null $seller
 * @method static \Database\Factories\OrderFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Order filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Order newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Order newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Order paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Order query()
 * @method static \Illuminate\Database\Eloquent\Builder|Order simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereAmountDelivered($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereCartId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereCatalogId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereCreated($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereDateConfirmed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereDateExpiredEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereDateRejectedEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereDeliveryNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereDeliveryStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereDiscount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereDiscountA($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereDiscountDelivered($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereDiscountMovil($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereDiscountType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereDiscountTypeSinceMovil($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereExpirationDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereIdKor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereInternalNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereLanguageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereLeido($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereNewAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereNewAddressJson($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereOrderComments($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereOrderNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order wherePaymentMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order wherePaymentStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order wherePortalVendor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereProforma($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereSendEmailOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereStatusId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereTipoD($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereTokenPass($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereTotalDelivered($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereTransactionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereUUID($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereUpdateBybackend($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereUpdated($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereVirifiedByportal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereWarehouseAssignDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereWarehouseUserId($value)
 * @property string|null $order_comments
 * @property string|null $delivery_notes
 * @property string $internal_notes
 * @property string $new_address
 * @property string|null $new_address_json
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
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereAddressId($value)
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Order',
    description: 'Order model'
)]
class Order extends Model
{

    use HasFactory;
    use Filterable;

    //use SoftDeletes;

    const CREATED_AT = 'created';
    const UPDATED_AT = 'updated';
    protected $table = 'orders';
    protected $primaryKey = 'order_id';


    protected $casts = [
        'company_id' => 'int',
        'seller_id' => 'int',
        'language_id' => 'int',
        'customer_id' => 'int',
        'payment_method' => 'int',
        'payment_status' => 'int',
        'delivery_status' => 'int',
        'discount' => 'float',
        'discount_type' => 'int',
        'created' => 'datetime',
        'updated' => 'datetime',
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
        'catalog_id' => 'int',
        'update_bybackend' => 'int',
        'discount_type_since_movil' => 'int',
        'discount_movil' => 'float',
        'leido' => 'int',
        'proforma' => 'int',
        'status_id' => 'int',
        'address_id' => 'int',
        'warehouse_id' => 'int',
        'warehouse_user_id' => 'int',
        'user_id_token' => 'int',
    ];

    protected $fillable = [
        'tipo_d',
        'order_number',
        'UUID',
        'company_id',
        'warehouse_id',
        'warehouse_user_id',
        'seller_id',
        'token_create',
        'user_id_token',
        'rol_token',
        'language_id',
        'customer_id',
        'payment_method',
        'payment_status',
        'transaction_id',
        'delivery_status',
        'discount',
        'discount_type',
        'created',
        'updated',
        'approved_by',
        'warehouse_assign_date',
        'source',
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
        'catalog_id',
        'update_bybackend',
        'discount_type_since_movil',
        'discount_movil',
        'leido',
        'proforma',
        'status_id',
        'address_id',
        'warehouse_user_id',
    ];

    protected $appends = [
        'subtotal',
        'subtotal_asignado',
        'descuento',
        'descuento_asignado',
        'total',
        'total_asignado',
    ];

    protected $with = ['order_details'];

    /**
     * Serialización API/listados: órdenes documento nunca exponen importes negativos.
     */
    public function toArray(): array
    {
        $array = parent::toArray();
        $tipoD = strtoupper((string) ($array['tipo_d'] ?? $this->attributes['tipo_d'] ?? 'D'));
        if ($tipoD === 'D') {
            foreach (['amount', 'total_amount', 'total', 'subtotal', 'discount_a', 'totsale'] as $key) {
                if (isset($array[$key]) && is_numeric($array[$key]) && (float) $array[$key] < 0) {
                    $array[$key] = abs((float) $array[$key]);
                }
            }
        }

        return $array;
    }

    public function getRouteKeyName()
    {
        return 'order_id';
    }

    public function address(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function catalog_cart()
    {
        return $this->belongsTo(CatalogCart::class, 'cart_id');
    }

    public function catalog()
    {
        return $this->belongsTo(Catalog::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }

    public function language()
    {
        return $this->belongsTo(Language::class);
    }

    public function notes()
    {
        return $this->hasMany(OrderNote::class, 'order_id', 'order_id');
    }

    public function order_details()
    {
        return $this->hasMany(OrderDetail::class, 'order_id', 'order_id');
    }

    public function seller()
    {
        return $this->belongsTo(Vendor::class, 'seller_id');
    }

    public function catalog_carts()
    {
        return $this->hasMany(CatalogCart::class);
    }

    public function delivered_orders()
    {
        return $this->hasMany(DeliveredOrder::class);
    }

    public function order_status_histories()
    {
        return $this->hasMany(OrderStatusHistory::class, 'order_id', 'order_id');
    }

    /**
     * Estado de la orden. En BD se enlaza por status_id (id_status).
     * El identificador de negocio es cod_status (status_orders.cod_status); usarlo para reglas y UI.
     */
    public function statusOrder()
    {
        return $this->belongsTo(StatusOrder::class, 'status_id', 'id_status');
    }

    /** @var bool|null Cache: existe columna orders.order_status (texto) */
    protected static ?bool $ordersOrderStatusTextColumnExists = null;

    protected static function ordersTableHasOrderStatusTextColumn(): bool
    {
        if (self::$ordersOrderStatusTextColumnExists === null) {
            self::$ordersOrderStatusTextColumnExists = Schema::hasColumn((new self())->getTable(), 'order_status');
        }

        return self::$ordersOrderStatusTextColumnExists;
    }

    /**
     * Nombre legible del estado según idioma de la orden (language_id 2 → español, si no → inglés).
     */
    public static function translatedStatusOrderLabel(?StatusOrder $statusOrder, int $languageId): string
    {
        if (! $statusOrder) {
            return '';
        }
        if ($languageId === 2) {
            return trim((string) ($statusOrder->name_status_spanish ?: $statusOrder->name_status_english ?: ''));
        }

        return trim((string) ($statusOrder->name_status_english ?: $statusOrder->name_status_spanish ?: ''));
    }

    /**
     * Rellena order_status (columna texto) desde status_id + language_id.
     */
    public function syncOrderStatusTextFromStatusId(): void
    {
        if (! self::ordersTableHasOrderStatusTextColumn()) {
            return;
        }
        if (! $this->status_id) {
            $this->setAttribute('order_status', '');

            return;
        }
        $statusOrder = $this->relationLoaded('statusOrder')
            ? $this->statusOrder
            : StatusOrder::query()->where('id_status', $this->status_id)->first();
        $this->setAttribute(
            'order_status',
            self::translatedStatusOrderLabel($statusOrder, (int) ($this->language_id ?? 1))
        );
    }

    protected static function booted(): void
    {
        static::saving(function (Order $order) {
            if (! self::ordersTableHasOrderStatusTextColumn()) {
                return;
            }
            if ($order->isDirty('status_id') || $order->isDirty('language_id')) {
                $order->syncOrderStatusTextFromStatusId();
            }
        });

        // Si la orden es (o pasa a ser) documento (D), forzar qty positivas en sus líneas.
        static::saved(function (Order $order) {
            if (strtoupper((string) ($order->tipo_d ?? '')) !== 'D') {
                return;
            }
            if (! $order->wasRecentlyCreated && ! $order->wasChanged('tipo_d')) {
                return;
            }
            app(\App\Services\Orders\EnsurePositiveQtyForDocumentOrdersService::class)
                ->repairOrder((int) $order->order_id);
        });
    }

    /**
     * Filtrar órdenes por cod_status (enlace lógico por estado).
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int|int[] $codStatus cod_status o array de cod_status
     */
    public function scopeWhereCodStatus($query, $codStatus)
    {
        $codes = is_array($codStatus) ? $codStatus : [$codStatus];
        return $query->whereHas('statusOrder', fn ($q) => $q->whereIn('cod_status', $codes));
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function warehouse_user()
    {
        return $this->belongsTo(WarehouseUser::class, 'warehouse_user_id', 'user_id');
    }

    /** @deprecated Usar {@see warehouse_user()}; se mantiene para rutas API históricas (`warehouseUser`). */
    public function warehouseUser()
    {
        return $this->warehouse_user();
    }

    public function worders_list()
    {
        return $this->hasOne(WordersList::class);
    }

    function generate_uuid(): string
    {
        $b = random_bytes(16);
        $b[6] = chr(ord($b[6]) & 0x0f | 0x40);
        $b[8] = chr(ord($b[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
    }

    public function generateOrderNumber($companyId, $orderType, $length = 6): string
    {
        $prefix = 'ORD';
        if ($orderType == 'C') {
            $prefix = 'OCN';
        }

        $randomString = $this->getNextPossibleOrderNumber($companyId, $length, $prefix);
        return "{$prefix}{$randomString}";
    }

    protected function getNextPossibleOrderNumber($companyId, $length, $prefix = 'ORD'): string
    {
        $nextOrderNumber = $this->getNextOrderNumber($companyId, $prefix);
        return sprintf("%0{$length}d", $nextOrderNumber);
    }

    public function getNextOrderNumber($companyId, $prefix = "ORD")
    {
        $result = DB::table('orders')
            ->selectRaw(
                'MAX(CAST(SUBSTR(order_number, 4) AS INT)) as orderNumber, SUBSTR(order_number, 1, 3) as prefix'
            )
            ->where('company_id', $companyId)
            ->groupBy('prefix')
            ->having('prefix', $prefix)
            ->first();

        $maxOrderNumber = $result->orderNumber ?? 0;
        $initial = $this->getInitialOrderNumber($companyId);
        if (isset($initial)) {
            $maxOrderNumber = max($maxOrderNumber, $initial);
        }
        return $maxOrderNumber + 1;
    }

    private function getInitialOrderNumber($companyId)
    {
        $initialForCompany = InitialOrderNumber::where('company_id', $companyId)->first();
        return $initialForCompany?->initial_number ?? null;
    }

    public function getTotals(bool $formatted = false): array
    {
        $this->loadMissing(['company.format_numeric', 'order_details']);

        $total = $this->total;
        $discount = $this->descuento;
        $sub_total = $this->subtotal;
        $qty_products = $this->order_details->count();

        if ($formatted) {
            $format = $this->company->format_numeric ?? FormatNumeric::default();
            $discount = $format->formatNumber($discount);
            $sub_total = $format->formatNumber($sub_total);
            $total = $format->formatNumber($total);
        }

        return [
            'total_orden' => $total,
            'discount_total' => $discount,
            'sub_total' => $sub_total,
            'cantidad_productos' => $qty_products,
        ];
    }

    protected function subtotal(): Attribute
    {
        return Attribute::get(function () {
            $tipoD = strtoupper((string) ($this->attributes['tipo_d'] ?? $this->tipo_d ?? 'D'));

            return collect($this->order_details)->sum(function ($d) use ($tipoD) {
                $qty = (float) ($d->qty ?? 0);
                // Órdenes documento: qty negativa no debe restar en totales de listado.
                if ($tipoD === 'D' && $qty < 0) {
                    $qty = abs($qty);
                }

                return $qty * (float) ($d->sale_price ?? 0);
            });
        });
    }

    protected function subtotalAsignado(): Attribute
    {
        return Attribute::get(fn () =>
        collect($this->order_details)->sum(fn ($d) =>
        $d->delivered_quantity > 0 ? $d->delivered_quantity * $d->sale_price : 0
        )
        );
    }

    protected function descuento(): Attribute
    {
        return Attribute::get(function () {
            if ($this->discount_type === 1) {
                return $this->subtotal * ($this->discount / 100);
            } elseif ($this->discount_type === 2) {
                return $this->discount;
            }
            return 0;
        });
    }

    protected function descuentoAsignado(): Attribute
    {
        return Attribute::get(function () {
            if ($this->discount_type === 1) {
                return $this->subtotal_asignado * ($this->discount / 100);
            } elseif ($this->discount_type === 2) {
                return $this->discount;
            }
            return 0;
        });
    }

    protected function total(): Attribute
    {
        return Attribute::get(function () {
            $sub = (float) $this->subtotal;
            $disc = (float) $this->descuento;
            if ($disc > $sub && $sub >= 0) {
                $disc = $sub;
            }
            $total = $sub - $disc;
            $tipoD = strtoupper((string) ($this->attributes['tipo_d'] ?? $this->tipo_d ?? 'D'));
            if ($tipoD === 'D') {
                return abs($total);
            }

            return $total;
        });
    }

    protected function totalAsignado(): Attribute
    {
        return Attribute::get(fn () =>
            $this->subtotal_asignado - $this->descuento_asignado
        );
    }
}
