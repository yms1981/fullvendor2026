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
 * Class Log
 *
 * @property int $log_id
 * @property int $user_id
 * @property int $company_id
 * @property string $log_type
 * @property string|null $notes
 * @property Carbon|null $created_on
 * @property Company $company
 * @property User $user
 * @package App\Models
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @method static \Database\Factories\LogFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Log filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Log newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Log newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Log onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Log paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Log query()
 * @method static \Illuminate\Database\Eloquent\Builder|Log simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Log whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Log whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Log whereCreatedOn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Log whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Log whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Log whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Log whereLogId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Log whereLogType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Log whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Log whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Log whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Log withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Log withoutTrashed()
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Log',
    description: 'Log model'
)]
class Log extends Model
{

    use HasFactory;
    use Filterable;
    use SoftDeletes;

    protected $table = 'logs';
    const CREATED_AT = 'created_on';

    protected $primaryKey = 'log_id';


    protected $casts = [
        'user_id' => 'int',
        'company_id' => 'int',
        'created_on' => 'datetime'
    ];

    protected $fillable = [
        'user_id',
        'company_id',
        'log_type',
        'notes',
        'created_on'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
