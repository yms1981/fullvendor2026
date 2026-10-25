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
 * Class Deviceinfo
 *
 * @property string $deviceinfo
 * @property string $so
 * @property string $version
 * @property Carbon $created
 * @property Carbon|null $updated_at
 * @property string|null $versionname
 * @package App\Models
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @method static \Database\Factories\DeviceinfoFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Deviceinfo filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Deviceinfo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Deviceinfo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Deviceinfo onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Deviceinfo paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Deviceinfo query()
 * @method static \Illuminate\Database\Eloquent\Builder|Deviceinfo simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Deviceinfo whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Deviceinfo whereCreated($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Deviceinfo whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Deviceinfo whereDeviceinfo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Deviceinfo whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Deviceinfo whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Deviceinfo whereSo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Deviceinfo whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Deviceinfo whereVersion($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Deviceinfo whereVersionname($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Deviceinfo withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Deviceinfo withoutTrashed()
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Deviceinfo',
    description: 'Deviceinfo model'
)]
class Deviceinfo extends Model
{

    use HasFactory;
    use Filterable;

    use SoftDeletes;

    const CREATED_AT = 'created';
    const UPDATED_AT = 'updated_at';
    protected $table = 'deviceinfos';
    protected $primaryKey = 'deviceinfo';
    public $incrementing = false;


    protected $casts = [
        'created' => 'datetime'
    ];

    protected $fillable = [
        'so',
        'version',
        'created',
        'versionname'
    ];
}
