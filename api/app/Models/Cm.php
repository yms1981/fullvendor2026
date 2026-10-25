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
 * Class Cm
 *
 * @property int $pageId
 * @property string|null $pageTitle
 * @property string|null $pageText
 * @property string|null $link
 * @property string|null $content
 * @property Carbon|null $created
 * @property Carbon|null $modified
 * @package App\Models
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @method static \Database\Factories\CmFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Cm filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Cm newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Cm newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Cm onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Cm paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Cm query()
 * @method static \Illuminate\Database\Eloquent\Builder|Cm simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Cm whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Cm whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cm whereCreated($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cm whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cm whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Cm whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Cm whereLink($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cm whereModified($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cm wherePageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cm wherePageText($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cm wherePageTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cm withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Cm withoutTrashed()
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Cm',
    description: 'Cm model'
)]
class Cm extends Model
{

    use HasFactory;
    use SoftDeletes;
    use Filterable;

    protected $table = 'cms';
    protected $primaryKey = 'pageId';

    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';

    protected $casts = [
        'created' => 'datetime',
        'modified' => 'datetime'
    ];

    protected $fillable = [
        'pageTitle',
        'pageText',
        'link',
        'content',
        'created',
        'modified'
    ];
}
