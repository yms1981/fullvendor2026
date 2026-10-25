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
 * Class StatusRol
 *
 * @property int $id
 * @property int|null $company_id
 * @property int|null $rol_id
 * @property Carbon|null $created_at
 * @property Carbon|null $update_at
 * @property int|null $id_status
 * @property Company|null $company
 * @property ManagerProfiles|null $rol
 * @package App\Models
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\StatusOrder|null $statusOrder
 * @method static \Database\Factories\StatusRolFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|StatusRol filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|StatusRol newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|StatusRol newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|StatusRol onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|StatusRol paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|StatusRol query()
 * @method static \Illuminate\Database\Eloquent\Builder|StatusRol simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|StatusRol whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|StatusRol whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StatusRol whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StatusRol whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StatusRol whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|StatusRol whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StatusRol whereIdStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StatusRol whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|StatusRol whereRolId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StatusRol whereUpdateAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StatusRol withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|StatusRol withoutTrashed()
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Statusrol',
    description: 'StatusRol model'
)]
class StatusRol extends Model
{

    use HasFactory;
    use Filterable;

    use SoftDeletes;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'update_at';protected $table = 'status_rol';


    protected $casts = [
        'company_id' => 'int',
        'rol_id' => 'int',
        'update_at' => 'datetime',
        'id_status' => 'int'
    ];

    protected $fillable = [
        'company_id',
        'rol_id',
        'update_at',
        'id_status'
    ];

	public function company()
	{
		return $this->belongsTo(Company::class);
	}

	public function rol()
	{
		return $this->belongsTo(ManagerProfiles::class);
	}

    public function statusOrder(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(StatusOrder::class, 'id_status', 'id_status');
    }
}
