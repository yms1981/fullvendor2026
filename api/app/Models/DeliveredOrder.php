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
 * Class DeliveredOrder
 *
 * @property int $delivered_id
 * @property int $order_id
 * @property int $product_id
 * @property float|null $delivered_quantity
 * @property Carbon $delivered_date
 * @property float|null $delivered_pack
 * @property Order $order
 * @property Product $product
 * @package App\Models
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @method static \Database\Factories\DeliveredOrderFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|DeliveredOrder filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|DeliveredOrder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DeliveredOrder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DeliveredOrder onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|DeliveredOrder paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|DeliveredOrder query()
 * @method static \Illuminate\Database\Eloquent\Builder|DeliveredOrder simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|DeliveredOrder whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|DeliveredOrder whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DeliveredOrder whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DeliveredOrder whereDeliveredDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DeliveredOrder whereDeliveredId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DeliveredOrder whereDeliveredPack($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DeliveredOrder whereDeliveredQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DeliveredOrder whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|DeliveredOrder whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|DeliveredOrder whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DeliveredOrder whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DeliveredOrder whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DeliveredOrder withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|DeliveredOrder withoutTrashed()
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Deliveredorder',
    description: 'DeliveredOrder model'
)]
class DeliveredOrder extends Model
{

    use HasFactory;
    use Filterable;
use SoftDeletes;
    protected $table = 'delivered_orders';
    protected $primaryKey = 'delivered_id';


    protected $casts = [
        'order_id' => 'int',
        'product_id' => 'int',
        'delivered_quantity' => 'float',
        'delivered_date' => 'datetime',
        'delivered_pack' => 'float'
    ];

    protected $fillable = [
        'order_id',
        'product_id',
        'delivered_quantity',
        'delivered_date',
        'delivered_pack'
    ];

	public function order()
	{
		return $this->belongsTo(Order::class);
	}

	public function product()
	{
		return $this->belongsTo(Product::class);
	}
}
