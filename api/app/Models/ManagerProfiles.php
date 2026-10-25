<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class ManagerProfiles
 *
 * @property int $rol_id
 * @property int|null $company_id
 * @property string|null $name
 * @property Carbon|null $created_date
 * @property Carbon|null $modified_date
 * @property int|null $is_active
 * @property string|null $deleted_at
 * @property Company|null $company
 * @property Collection|Company[] $companies
 * @property Collection|Navbar[] $navbars
 * @property Collection|StatusRol[] $statuses
 * @package App\Models
 * @property-read Collection<int, \App\Models\CompanyUser> $companyUsers
 * @property-read int|null $company_users_count
 * @property-read int|null $statuses_count
 * @method static \Database\Factories\ManagerProfilesFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|ManagerProfiles filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|ManagerProfiles newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ManagerProfiles newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ManagerProfiles onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|ManagerProfiles paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|ManagerProfiles query()
 * @method static \Illuminate\Database\Eloquent\Builder|ManagerProfiles simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|ManagerProfiles whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|ManagerProfiles whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ManagerProfiles whereCreatedDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ManagerProfiles whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ManagerProfiles whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|ManagerProfiles whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ManagerProfiles whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|ManagerProfiles whereModifiedDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ManagerProfiles whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ManagerProfiles whereRolId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ManagerProfiles withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|ManagerProfiles withoutTrashed()
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'ManagerProfiles',
    description: 'ManagerProfiles model'
)]
class ManagerProfiles extends Model
{

    use HasFactory;
    use Filterable;
    use SoftDeletes;

    const CREATED_AT = 'created_date';
    const UPDATED_AT = 'modified_date';
    protected $table = 'manager_profiles';
    protected $primaryKey = 'rol_id';


    protected $casts = [
        'company_id' => 'int',
        'created_date' => 'datetime',
        'modified_date' => 'datetime',
        'is_active' => 'int'
    ];

    protected $fillable = [
        'company_id',
        'name',
        'created_date',
        'modified_date',
        'is_active'
    ];

	public function company()
	{
		return $this->belongsTo(Company::class);
	}

    public function companyUsers(): MorphMany
    {
        return $this->morphMany(CompanyUser::class, 'profileable');
    }

//	public function companies()
//	{
//		return $this->hasMany(Company::class);
//	}
//
//	public function navbars()
//	{
//		return $this->belongsToMany(Navbar::class, 'navbar_rol_id')
//					->withPivot('id', 'company_id', 'fhater_id');
//	}
//
	public function statuses(): HasMany
    {
		return $this->hasMany(StatusRol::class, 'rol_id', 'rol_id');
	}
}
