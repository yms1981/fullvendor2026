<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Version
 *
 * @property int|null $id
 * @property int $company_id
 * @package App\Models
 * @method static \Illuminate\Database\Eloquent\Builder|Version filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Version newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Version newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Version paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Version query()
 * @method static \Illuminate\Database\Eloquent\Builder|Version simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Version whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Version whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Version whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Version whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Version whereLike($column, $value, $boolean = 'and')
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Version',
    description: 'Version model'
)]
class Version extends Model
{

    use HasFactory;
    use Filterable;

    protected $table = 'version';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'id' => 'int',
        'company_id' => 'int'
    ];

    protected $fillable = [
        'id',
        'company_id'
    ];
}
