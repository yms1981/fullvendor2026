<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class CacheLock
 *
 * @property string $key
 * @property string $owner
 * @property int $expiration
 * @package App\Models
 * @method static \Database\Factories\CacheLockFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|CacheLock filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|CacheLock newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CacheLock newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CacheLock paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|CacheLock query()
 * @method static \Illuminate\Database\Eloquent\Builder|CacheLock simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|CacheLock whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|CacheLock whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|CacheLock whereExpiration($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CacheLock whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CacheLock whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|CacheLock whereOwner($value)
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Cachelock',
    description: 'CacheLock model'
)]
class CacheLock extends Model
{

    use HasFactory;
    use Filterable;

    protected $table = 'cache_locks';
    protected $primaryKey = 'key';
    public $incrementing = false;
    public $timestamps = false;


    protected $casts = [
        'expiration' => 'int'
    ];

    protected $fillable = [
        'owner',
        'expiration'
    ];
}
