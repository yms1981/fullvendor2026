<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class CatalogRequest
 *
 * @property int $request_id
 * @property int $user_id
 * @property int $customer_id
 * @property int $catalog_id
 * @property int $access_count
 * @property string $token
 * @property string|null $descriptions
 * @property Carbon|null $created
 * @property Carbon $updated
 * @property int $status
 * @property Customer $customer
 * @property User $user
 * @property Collection|CatalogSentMail[] $catalog_sent_mails
 * @package App\Models
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read int|null $catalog_sent_mails_count
 * @method static \Database\Factories\CatalogRequestFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogRequest filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogRequest onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogRequest paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogRequest simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogRequest whereAccessCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogRequest whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogRequest whereCatalogId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogRequest whereCreated($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogRequest whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogRequest whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogRequest whereDescriptions($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogRequest whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogRequest whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogRequest whereRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogRequest whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogRequest whereToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogRequest whereUpdated($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogRequest whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogRequest withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogRequest withoutTrashed()
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Catalogrequest',
    description: 'CatalogRequest model'
)]
class CatalogRequest extends Model
{

    use HasFactory;
    use Filterable;
use SoftDeletes;
    protected $table = 'catalog_request';
    const CREATED_AT = 'created';
    const UPDATED_AT = 'updated';protected $primaryKey = 'request_id';


    protected $casts = [
        'user_id' => 'int',
        'customer_id' => 'int',
        'created' => 'datetime',
        'updated' => 'datetime',
        'status' => 'int'
    ];

    protected $fillable = [
        'user_id',
        'customer_id',
        'descriptions',
        'created',
        'updated',
        'status'
    ];

	public function customer()
	{
		return $this->belongsTo(Customer::class);
	}

	public function user()
	{
		return $this->belongsTo(User::class);
	}

	public function catalog_sent_mails()
	{
		return $this->hasMany(CatalogSentMail::class, 'catlog_reqest_id');
	}
}
