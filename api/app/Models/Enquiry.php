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
 * Class Enquiry
 *
 * @property int $enquiryId
 * @property string|null $name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $subject
 * @property string|null $message
 * @property Carbon|null $created
 * @property int|null $status
 * @package App\Models
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @method static \Database\Factories\EnquiryFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Enquiry filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Enquiry newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Enquiry newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Enquiry onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Enquiry paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Enquiry query()
 * @method static \Illuminate\Database\Eloquent\Builder|Enquiry simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Enquiry whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Enquiry whereCreated($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Enquiry whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Enquiry whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Enquiry whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Enquiry whereEnquiryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Enquiry whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Enquiry whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Enquiry whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Enquiry wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Enquiry whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Enquiry whereSubject($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Enquiry withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Enquiry withoutTrashed()
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Enquiry',
    description: 'Enquiry model'
)]
class Enquiry extends Model
{

    use HasFactory;
    use Filterable;

    use SoftDeletes;protected $table = 'enquiries';
    protected $primaryKey = 'enquiryId';
    public $timestamps = false;

    protected $casts = [
        'created' => 'datetime',
        'status' => 'int'
    ];

    protected $fillable = [
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'created',
        'status'
    ];
}
