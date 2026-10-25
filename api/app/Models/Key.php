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
 * Class Key
 *
 * @property int $id
 * @property int $user_id
 * @property string $key
 * @property int $level
 * @property bool $ignore_limits
 * @property bool $is_private_key
 * @property string|null $ip_addresses
 * @property Carbon $date_created
 * @property User $user
 * @package App\Models
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @method static \Database\Factories\KeyFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Key filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Key newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Key newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Key onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Key paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Key query()
 * @method static \Illuminate\Database\Eloquent\Builder|Key simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Key whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Key whereDateCreated($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Key whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Key whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Key whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Key whereIgnoreLimits($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Key whereIpAddresses($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Key whereIsPrivateKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Key whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Key whereLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Key whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Key whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Key whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Key withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Key withoutTrashed()
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Key',
    description: 'Key model'
)]
class Key extends Model
{

    use HasFactory;
    use Filterable;
use SoftDeletes;

    const CREATED_AT = 'date_created';
    protected $table = 'keys';

    protected $casts = [
        'user_id' => 'int',
        'level' => 'int',
        'ignore_limits' => 'bool',
        'is_private_key' => 'bool',
        'date_created' => 'datetime'
    ];

    protected $fillable = [
        'user_id',
        'key',
        'level',
        'ignore_limits',
        'is_private_key',
        'ip_addresses',
        'date_created'
    ];

	public function user()
	{
		return $this->belongsTo(User::class);
	}
}
