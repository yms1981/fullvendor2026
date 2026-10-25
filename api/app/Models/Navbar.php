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
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Navbar
 *
 * @property int $navbar_id
 * @property string|null $url_nav
 * @property string|null $identify
 * @property string|null $name_eng
 * @property string|null $name_spa
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int|null $father_id
 * @property string|null $icon
 * @property int|null $order
 * @property int|null $config
 * @property int|null $is_active_to_rol
 * @property Collection|ManagerProfiles[] $rols
 * @package App\Models
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read int|null $rols_count
 * @method static \Database\Factories\NavbarFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Navbar filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Navbar newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Navbar newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Navbar onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Navbar paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Navbar query()
 * @method static \Illuminate\Database\Eloquent\Builder|Navbar simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Navbar whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Navbar whereConfig($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Navbar whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Navbar whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Navbar whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Navbar whereFatherId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Navbar whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Navbar whereIdentify($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Navbar whereIsActiveToRol($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Navbar whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Navbar whereNameEng($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Navbar whereNameSpa($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Navbar whereNavbarId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Navbar whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Navbar whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Navbar whereUrlNav($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Navbar withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Navbar withoutTrashed()
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Navbar',
    description: 'Navbar model'
)]
class Navbar extends Model
{

    use HasFactory;
    use Filterable;

    use SoftDeletes;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';protected $table = 'navbars';
    protected $primaryKey = 'navbar_id';

    protected $casts = [
        'father_id' => 'int',
        'order' => 'int',
        'config' => 'int',
        'is_active_to_rol' => 'int'
    ];

    protected $fillable = [
        'url_nav',
        'identify',
        'name_eng',
        'name_spa',
        'father_id',
        'icon',
        'order',
        'config',
        'is_active_to_rol'
    ];

	public function rols()
	{
		return $this->belongsToMany(ManagerProfiles::class, 'navbar_rol_id')
					->withPivot('id', 'company_id', 'fhater_id');
	}
}
