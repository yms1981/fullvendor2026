<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class NavbarRolId
 *
 * @property int $id
 * @property int|null $navbar_id
 * @property int|null $rol_id
 * @property int|null $company_id
 * @property int|null $fhater_id
 * @property Company|null $company
 * @property Navbar|null $navbar
 * @property ManagerProfiles|null $rol
 * @package App\Models
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @method static \Database\Factories\NavbarRolIdFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|NavbarRolId filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|NavbarRolId newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|NavbarRolId newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|NavbarRolId onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|NavbarRolId paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|NavbarRolId query()
 * @method static \Illuminate\Database\Eloquent\Builder|NavbarRolId simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|NavbarRolId whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|NavbarRolId whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NavbarRolId whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NavbarRolId whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NavbarRolId whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|NavbarRolId whereFhaterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NavbarRolId whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NavbarRolId whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|NavbarRolId whereNavbarId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NavbarRolId whereRolId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NavbarRolId whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NavbarRolId withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|NavbarRolId withoutTrashed()
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Navbarrolid',
    description: 'NavbarRolId model'
)]
class NavbarRolId extends Model
{

    use HasFactory;
    use SoftDeletes;
    use Filterable;

    protected $table = 'navbar_rol_id';
    public $timestamps = false;


    protected $casts = [
        'navbar_id' => 'int',
        'rol_id' => 'int',
        'company_id' => 'int',
        'fhater_id' => 'int'
    ];

    protected $fillable = [
        'navbar_id',
        'rol_id',
        'company_id',
        'fhater_id'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function navbar()
    {
        return $this->belongsTo(Navbar::class);
    }

    public function rol()
    {
        return $this->belongsTo(ManagerProfiles::class);
    }
}
