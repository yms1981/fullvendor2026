<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Consoleuser
 *
 * @property int $id
 * @property string $email
 * @property string|null $password
 * @property string|null $rol
 * @package App\Models
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @method static \Database\Factories\ConsoleuserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Consoleuser filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Consoleuser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Consoleuser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Consoleuser onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Consoleuser paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Consoleuser query()
 * @method static \Illuminate\Database\Eloquent\Builder|Consoleuser simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Consoleuser whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Consoleuser whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Consoleuser whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Consoleuser whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Consoleuser whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Consoleuser whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Consoleuser whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Consoleuser wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Consoleuser whereRol($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Consoleuser whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Consoleuser withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Consoleuser withoutTrashed()
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Consoleuser',
    description: 'Consoleuser model'
)]
class Consoleuser extends Model
{
    use SoftDeletes;
    use HasFactory;
    use Filterable;

    protected $table = 'consoleusers';
    public $timestamps = false;

    protected $hidden = [
        'password'
    ];

    protected $fillable = [
        'email',
        'password',
        'rol'
    ];
}
