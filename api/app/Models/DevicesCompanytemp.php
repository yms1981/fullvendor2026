<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class DevicesCompanytemp
 *
 * @property int $company_id
 * @property string|null $deviceinfo
 * @package App\Models
 * @method static \Database\Factories\DevicesCompanytempFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|DevicesCompanytemp filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|DevicesCompanytemp newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DevicesCompanytemp newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DevicesCompanytemp paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|DevicesCompanytemp query()
 * @method static \Illuminate\Database\Eloquent\Builder|DevicesCompanytemp simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|DevicesCompanytemp whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|DevicesCompanytemp whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DevicesCompanytemp whereDeviceinfo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DevicesCompanytemp whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|DevicesCompanytemp whereLike($column, $value, $boolean = 'and')
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Devicescompanytemp',
    description: 'DevicesCompanytemp model'
)]
class DevicesCompanytemp extends Model
{

    use HasFactory;
    use Filterable;

    protected $table = 'devicesCompanytemp';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'company_id' => 'int'
    ];

    protected $fillable = [
        'company_id',
        'deviceinfo'
    ];
}
