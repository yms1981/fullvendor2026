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
 * Class ChangesApp
 *
 * @property int $id
 * @property int $company_id
 * @property Carbon $created
 * @property int $read
 * @property string|null $email
 * @property int|null $user_id
 * @property Company $company
 * @property User|null $user
 * @package App\Models
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @method static \Database\Factories\ChangesAppFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|ChangesApp filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|ChangesApp newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ChangesApp newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ChangesApp onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|ChangesApp paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|ChangesApp query()
 * @method static \Illuminate\Database\Eloquent\Builder|ChangesApp simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|ChangesApp whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|ChangesApp whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChangesApp whereCreated($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChangesApp whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChangesApp whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChangesApp whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|ChangesApp whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChangesApp whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|ChangesApp whereRead($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChangesApp whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChangesApp whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChangesApp withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|ChangesApp withoutTrashed()
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Changesapp',
    description: 'ChangesApp model'
)]
class ChangesApp extends Model
{

    use HasFactory;
    use Filterable;

    use SoftDeletes;

    const CREATED_AT = 'created';protected $table = 'changes_app';


    protected $casts = [
        'company_id' => 'int',
        'created' => 'datetime',
        'read' => 'int',
        'user_id' => 'int'
    ];

    protected $fillable = [
        'company_id',
        'created',
        'read',
        'email',
        'user_id'
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
