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
 * Class OrderStatusHistory
 *
 * @property int $history_id
 * @property int|null $order_id
 * @property int|null $company_id
 * @property int|null $user_id
 * @property int|null $cod_status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Company|null $company
 * @property Order|null $order
 * @property User|null $user
 * @package App\Models
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\StatusOrder|null $status
 * @method static \Database\Factories\OrderStatusHistoryFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|OrderStatusHistory filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderStatusHistory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderStatusHistory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderStatusHistory onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderStatusHistory paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderStatusHistory query()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderStatusHistory simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderStatusHistory whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|OrderStatusHistory whereCodStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderStatusHistory whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderStatusHistory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderStatusHistory whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderStatusHistory whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|OrderStatusHistory whereHistoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderStatusHistory whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|OrderStatusHistory whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderStatusHistory whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderStatusHistory whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderStatusHistory withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderStatusHistory withoutTrashed()
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Orderstatushistory',
    description: 'OrderStatusHistory model'
)]
class OrderStatusHistory extends Model
{

    use HasFactory;
    use Filterable;

    use SoftDeletes;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';protected $table = 'order_status_history';
    protected $primaryKey = 'history_id';

    protected $casts = [
        'order_id' => 'int',
        'company_id' => 'int',
        'user_id' => 'int',
        'cod_status' => 'int'
    ];

    protected $fillable = [
        'order_id',
        'company_id',
        'user_id',
        'cod_status'
    ];

	public function company()
	{
		return $this->belongsTo(Company::class);
	}

	public function order()
	{
		return $this->belongsTo(Order::class, 'order_id', 'order_id');
	}

	public function user()
	{
		return $this->belongsTo(User::class);
	}

    public function status() {
        //TODO: this should be referenced via status_id instead of the code
        return $this->belongsTo(StatusOrder::class,'cod_status','cod_status');
    }
}
