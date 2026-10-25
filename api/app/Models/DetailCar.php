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
 * Class DetailCar
 *
 * @property int $detail_id
 * @property int|null $product_id
 * @property string|null $token_car
 * @property int|null $customer_id
 * @property int|null $company_id
 * @property int|null $qty
 * @property Carbon|null $created
 * @property float|null $sale_price
 * @property string|null $comment
 * @property int|null $cart_id
 * @property string|null $session_id
 * @property int|null $cookie_id
 * @property CatalogCart|null $catalog_cart
 * @property Company|null $company
 * @property Customer|null $customer
 * @property Product|null $product
 * @package App\Models
 * @method static \Database\Factories\DetailCarFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|DetailCar filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|DetailCar newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DetailCar newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DetailCar onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|DetailCar paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|DetailCar query()
 * @method static \Illuminate\Database\Eloquent\Builder|DetailCar simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|DetailCar whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|DetailCar whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|DetailCar whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|DetailCar withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|DetailCar withoutTrashed()
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DetailCar whereCartId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DetailCar whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DetailCar whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DetailCar whereCookieId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DetailCar whereCreated($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DetailCar whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DetailCar whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DetailCar whereDetailId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DetailCar whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DetailCar whereQty($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DetailCar whereSalePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DetailCar whereSessionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DetailCar whereTokenCar($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DetailCar whereUpdatedAt($value)
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Detailcar',
    description: 'DetailCar model'
)]
class DetailCar extends Model
{

    use HasFactory;
    use Filterable;

    use SoftDeletes;

    const CREATED_AT = 'created';protected $table = 'detail_car';
    protected $primaryKey = 'detail_id';


    protected $casts = [
        'product_id' => 'int',
        'customer_id' => 'int',
        'company_id' => 'int',
        'qty' => 'int',
        'created' => 'datetime',
        'sale_price' => 'float',
        'cart_id' => 'int',
        'cookie_id' => 'int'
    ];

    protected $fillable = [
        'product_id',
        'token_car',
        'customer_id',
        'company_id',
        'qty',
        'created',
        'sale_price',
        'comment',
        'cart_id',
        'session_id',
        'cookie_id'
    ];

	public function catalog_cart()
	{
		return $this->belongsTo(CatalogCart::class, 'cart_id');
	}

	public function company()
	{
		return $this->belongsTo(Company::class);
	}

	public function customer()
	{
		return $this->belongsTo(Customer::class);
	}

	public function product()
	{
        return $this->belongsTo(Product::class, 'product_id', 'pro_id');
	}
}
