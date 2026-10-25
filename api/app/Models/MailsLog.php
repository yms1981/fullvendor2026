<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class MailsLog
 *
 * @property int $id
 * @property string $customers
 * @property string $subject
 * @property string $content
 * @property Carbon $fecha
 * @package App\Models
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @method static \Database\Factories\MailsLogFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|MailsLog filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|MailsLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MailsLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MailsLog onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|MailsLog paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|MailsLog query()
 * @method static \Illuminate\Database\Eloquent\Builder|MailsLog simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|MailsLog whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|MailsLog whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MailsLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MailsLog whereCustomers($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MailsLog whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MailsLog whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|MailsLog whereFecha($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MailsLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MailsLog whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|MailsLog whereSubject($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MailsLog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MailsLog withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|MailsLog withoutTrashed()
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Mailslog',
    description: 'MailsLog model'
)]
class MailsLog extends Model
{

    use HasFactory;
    use Filterable;

    use SoftDeletes;protected $table = 'mails_logs';


    protected $casts = [
        'fecha' => 'datetime'
    ];

    protected $fillable = [
        'customers',
        'subject',
        'content',
        'fecha'
    ];
}
