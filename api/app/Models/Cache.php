<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Cache
 *
 * @property string $key
 * @property string $value
 * @property int $expiration
 * @package App\Models
 * @method static \Database\Factories\CacheFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Cache filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Cache newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Cache newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Cache paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Cache query()
 * @method static \Illuminate\Database\Eloquent\Builder|Cache simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Cache whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Cache whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Cache whereExpiration($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cache whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cache whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Cache whereValue($value)
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Cache',
    description: 'Cache model'
)]
class Cache extends Model
{

    use HasFactory;
    use Filterable;

    protected $table = 'cache';
    protected $primaryKey = 'key';
    public $incrementing = false;
    public $timestamps = false;


    protected $casts = [
        'expiration' => 'int'
    ];

    protected $fillable = [
        'value',
        'expiration'
    ];
}
