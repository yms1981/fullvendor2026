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
 * Class Consoleconfig
 *
 * @property int $id
 * @property string|null $token
 * @property int|null $company_id
 * @property string|null $username
 * @property string|null $password
 * @property string|null $email
 * @property string|null $address
 * @property string|null $telephone
 * @property string|null $app
 * @property string|null $rol
 * @property Company|null $company
 * @package App\Models
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @method static \Database\Factories\ConsoleconfigFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Consoleconfig filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Consoleconfig newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Consoleconfig newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Consoleconfig onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Consoleconfig paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Consoleconfig query()
 * @method static \Illuminate\Database\Eloquent\Builder|Consoleconfig simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Consoleconfig whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Consoleconfig whereApp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Consoleconfig whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Consoleconfig whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Consoleconfig whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Consoleconfig whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Consoleconfig whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Consoleconfig whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Consoleconfig whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Consoleconfig whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Consoleconfig wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Consoleconfig whereRol($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Consoleconfig whereTelephone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Consoleconfig whereToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Consoleconfig whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Consoleconfig whereUsername($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Consoleconfig withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Consoleconfig withoutTrashed()
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Consoleconfig',
    description: 'Consoleconfig model'
)]
class Consoleconfig extends Model
{

    use HasFactory;
    use Filterable;

    protected $table = 'consoleconfig';
    public $timestamps = false;
    use SoftDeletes;


    protected $casts = [
        'company_id' => 'int'
    ];

    protected $hidden = [
        'token',
        'password'
    ];

    protected $fillable = [
        'token',
        'company_id',
        'username',
        'password',
        'email',
        'address',
        'telephone',
        'app',
        'rol'
    ];

	public function company()
	{
		return $this->belongsTo(Company::class);
	}
}
