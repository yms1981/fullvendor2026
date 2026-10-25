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
 * Class WarehouseUser
 *
 * @property int $user_id
 * @property int|null $company_id
 * @property Carbon|null $created
 * @property string|null $deleted_at
 * @property Company|null $company
 * @package App\Models
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Database\Factories\WarehouseUserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|WarehouseUser filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|WarehouseUser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WarehouseUser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WarehouseUser onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|WarehouseUser paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|WarehouseUser query()
 * @method static \Illuminate\Database\Eloquent\Builder|WarehouseUser simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|WarehouseUser whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|WarehouseUser whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WarehouseUser whereCreated($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WarehouseUser whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WarehouseUser whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|WarehouseUser whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|WarehouseUser whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WarehouseUser whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WarehouseUser whereUsername($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WarehouseUser withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|WarehouseUser withoutTrashed()
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Warehouseuser',
    description: 'WarehouseUser model'
)]
class WarehouseUser extends Model
{

    use HasFactory;
    use Filterable;
    use SoftDeletes;

    const CREATED_AT = 'created';
    protected $table = 'warehouse_users';
    protected $primaryKey = 'user_id';


    protected $casts = [
        'user_id' => 'int',
        'company_id' => 'int',
        'warehouse_id' => 'int'
    ];

    protected $fillable = [
        'user_id',
        'company_id',
        'warehouse_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
	public function company()
	{
		return $this->belongsTo(Company::class, 'company_id','company_id');
	}

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }
}
