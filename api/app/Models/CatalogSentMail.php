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
 * Class CatalogSentMail
 *
 * @property int $catalog_sent_id
 * @property int|null $company_id
 * @property int $catlog_reqest_id
 * @property int $hide_price
 * @property int $catalog_id
 * @property string|null $catalog_text
 * @property string|null $catalog_send_type
 * @property string|null $catalog_email_text
 * @property Carbon|null $send_date
 * @property string|null $customer_emails
 * @property string|null $customer_id
 * @property Carbon|null $expiration
 * @property Catalog $catalog
 * @property CatalogRequest $catalog_request
 * @property Company|null $company
 * @package App\Models
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Customer|null $customer
 * @method static \Database\Factories\CatalogSentMailFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogSentMail filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogSentMail newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogSentMail newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogSentMail onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogSentMail paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogSentMail query()
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogSentMail simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogSentMail whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogSentMail whereCatalogEmailText($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogSentMail whereCatalogId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogSentMail whereCatalogSendType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogSentMail whereCatalogSentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogSentMail whereCatalogText($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogSentMail whereCatlogReqestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogSentMail whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogSentMail whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogSentMail whereCustomerEmails($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogSentMail whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogSentMail whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogSentMail whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogSentMail whereExpiration($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogSentMail whereHidePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogSentMail whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogSentMail whereSendDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogSentMail whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogSentMail withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogSentMail withoutTrashed()
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Catalogsentmail',
    description: 'CatalogSentMail model'
)]
class CatalogSentMail extends Model
{

    use HasFactory;
    use Filterable;

    use SoftDeletes;protected $table = 'catalog_sent_mail';
    protected $primaryKey = 'catalog_sent_id';


    protected $casts = [
        'company_id' => 'int',
        'catlog_reqest_id' => 'int',
        'hide_price' => 'int',
        'catalog_id' => 'int',
        'send_date' => 'datetime',
        'expiration' => 'datetime'
    ];

    protected $fillable = [
        'company_id',
        'catlog_reqest_id',
        'hide_price',
        'catalog_id',
        'catalog_text',
        'catalog_send_type',
        'catalog_email_text',
        'send_date',
        'customer_emails',
        'customer_id',
        'expiration'
    ];

	public function catalog()
	{
		return $this->belongsTo(Catalog::class);
	}

	public function catalog_request()
	{
		return $this->belongsTo(CatalogRequest::class, 'catlog_reqest_id');
	}

	public function company()
	{
		return $this->belongsTo(Company::class);
	}

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customerIds', 'customer_id');
    }
}
