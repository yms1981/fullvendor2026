<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class RsOrder
 *
 * @property int $order_id
 * @property float|null $amount
 * @property float|null $discount
 * @property float|null $amount_delivered
 * @property float|null $discount_delivered
 * @property float|null $total_amount
 * @property float|null $total_delivered
 * @package App\Models
 * @method static \Illuminate\Database\Eloquent\Builder|RsOrder filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|RsOrder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RsOrder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RsOrder paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|RsOrder query()
 * @method static \Illuminate\Database\Eloquent\Builder|RsOrder simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|RsOrder whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RsOrder whereAmountDelivered($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RsOrder whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|RsOrder whereDiscount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RsOrder whereDiscountDelivered($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RsOrder whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|RsOrder whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|RsOrder whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RsOrder whereTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RsOrder whereTotalDelivered($value)
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Rsorder',
    description: 'RsOrder model'
)]
class RsOrder extends Model
{

    use HasFactory;
    use Filterable;

    protected $table = 'rs_orders';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'order_id' => 'int',
        'amount' => 'float',
        'discount' => 'float',
        'amount_delivered' => 'float',
        'discount_delivered' => 'float',
        'total_amount' => 'float',
        'total_delivered' => 'float'
    ];

    protected $fillable = [
        'order_id',
        'amount',
        'discount',
        'amount_delivered',
        'discount_delivered',
        'total_amount',
        'total_delivered'
    ];
}
