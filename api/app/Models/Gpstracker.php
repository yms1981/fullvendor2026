<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Gpstracker
 *
 * @property int $id
 * @property int $company_id
 * @property int $user_id
 * @property float $longitude
 * @property float $latitude
 * @property Carbon $created
 * @property int|null $login
 * @property string|null $address
 * @property string|null $deviceinfo
 * @package App\Models
 * @method static \Database\Factories\GpstrackerFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Gpstracker filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Gpstracker newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Gpstracker newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Gpstracker paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Gpstracker query()
 * @method static \Illuminate\Database\Eloquent\Builder|Gpstracker simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Gpstracker whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Gpstracker whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Gpstracker whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Gpstracker whereCreated($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Gpstracker whereDeviceinfo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Gpstracker whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Gpstracker whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Gpstracker whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Gpstracker whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Gpstracker whereLogin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Gpstracker whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Gpstracker whereUserId($value)
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Gpstracker',
    description: 'Gpstracker model'
)]
class Gpstracker extends Model
{

    use HasFactory;
    use Filterable;

    protected $table = 'gpstracker';
    public $timestamps = false;

    protected $casts = [
        'company_id' => 'int',
        'user_id' => 'int',
        'longitude' => 'float',
        'latitude' => 'float',
        'created' => 'datetime',
        'login' => 'int'
    ];

    protected $fillable = [
        'company_id',
        'user_id',
        'longitude',
        'latitude',
        'created',
        'login',
        'address',
        'deviceinfo'
    ];
}
