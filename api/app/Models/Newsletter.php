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
 * Class Newsletter
 *
 * @property int $newsletterId
 * @property string|null $email
 * @property Carbon|null $created
 * @property int|null $status
 * @package App\Models
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @method static \Database\Factories\NewsletterFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Newsletter filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Newsletter newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Newsletter newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Newsletter onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Newsletter paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Newsletter query()
 * @method static \Illuminate\Database\Eloquent\Builder|Newsletter simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Newsletter whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Newsletter whereCreated($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Newsletter whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Newsletter whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Newsletter whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Newsletter whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Newsletter whereNewsletterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Newsletter whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Newsletter whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Newsletter withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Newsletter withoutTrashed()
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Newsletter',
    description: 'Newsletter model'
)]
class Newsletter extends Model
{

    use HasFactory;
    use Filterable;

    use SoftDeletes;

    const CREATED_AT = 'created';protected $table = 'newsletter';
    protected $primaryKey = 'newsletterId';


    protected $casts = [
        'created' => 'datetime',
        'status' => 'int'
    ];

    protected $fillable = [
        'email',
        'created',
        'status'
    ];
}
