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
 * Class CompanyStatusOrder
 *
 * @property int $id
 * @property int|null $cod_status
 * @property int|null $company_id
 * @property string|null $name
 * @property string|null $email
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Company|null $company
 * @package App\Models
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @method static \Database\Factories\CompanyStatusOrderFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyStatusOrder filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyStatusOrder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyStatusOrder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyStatusOrder onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyStatusOrder paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyStatusOrder query()
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyStatusOrder simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyStatusOrder whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyStatusOrder whereCodStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyStatusOrder whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyStatusOrder whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyStatusOrder whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyStatusOrder whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyStatusOrder whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyStatusOrder whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyStatusOrder whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyStatusOrder whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyStatusOrder whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyStatusOrder withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyStatusOrder withoutTrashed()
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Companystatusorder',
    description: 'CompanyStatusOrder model'
)]
class CompanyStatusOrder extends Model
{

    use HasFactory;
    use Filterable;

    use SoftDeletes;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

	protected $table = 'company_status_order';

    protected $casts = [
        'cod_status' => 'int',
        'company_id' => 'int'
    ];

    protected $fillable = [
        'cod_status',
        'company_id',
        'name',
        'email'
    ];

	public function company()
	{
		return $this->belongsTo(Company::class);
	}
}
