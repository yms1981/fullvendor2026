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
 * Class Gpslog
 *
 * @property int $id
 * @property int $company_id
 * @property int $user_id
 * @property int $login
 * @property string $infotext
 * @property Carbon $created
 * @package App\Models
 * @method static \Database\Factories\GpslogFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Gpslog filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Gpslog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Gpslog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Gpslog paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Gpslog query()
 * @method static \Illuminate\Database\Eloquent\Builder|Gpslog simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Gpslog whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Gpslog whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Gpslog whereCreated($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Gpslog whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Gpslog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Gpslog whereInfotext($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Gpslog whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Gpslog whereLogin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Gpslog whereUserId($value)
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Gpslog',
    description: 'Gpslog model'
)]
class Gpslog extends Model
{

    use HasFactory;
    use Filterable;

    protected $table = 'gpslog';
    public $timestamps = false;

    protected $casts = [
        'company_id' => 'int',
        'user_id' => 'int',
        'login' => 'int',
        'created' => 'datetime'
    ];

    protected $fillable = [
        'company_id',
        'user_id',
        'login',
        'infotext',
        'created'
    ];
}
