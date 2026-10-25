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
 * Class Devicebycompanylist
 *
 * @property string $deviceinfo
 * @property string $so
 * @property string $version
 * @property Carbon $created
 * @property Carbon|null $updated_at
 * @property string|null $versionname
 * @property int|null $company_id
 * @package App\Models
 * @method static \Database\Factories\DevicebycompanylistFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Devicebycompanylist filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Devicebycompanylist newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Devicebycompanylist newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Devicebycompanylist paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Devicebycompanylist query()
 * @method static \Illuminate\Database\Eloquent\Builder|Devicebycompanylist simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Devicebycompanylist whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Devicebycompanylist whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Devicebycompanylist whereCreated($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Devicebycompanylist whereDeviceinfo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Devicebycompanylist whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Devicebycompanylist whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Devicebycompanylist whereSo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Devicebycompanylist whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Devicebycompanylist whereVersion($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Devicebycompanylist whereVersionname($value)
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Devicebycompanylist',
    description: 'Devicebycompanylist model'
)]
class Devicebycompanylist extends Model
{

    use HasFactory;
    use Filterable;

    protected $table = 'devicebycompanylist';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'created' => 'datetime',
        'company_id' => 'int'
    ];

    protected $fillable = [
        'deviceinfo',
        'so',
        'version',
        'created',
        'versionname',
        'company_id'
    ];
}
