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
 * Class CompanyContact
 *
 * @property int $c_id
 * @property int|null $company_id
 * @property string|null $name
 * @property string|null $email
 * @property string|null $website
 * @property string|null $phone
 * @property string|null $cell_phone
 * @property Company|null $company
 * @package App\Models
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @method static \Database\Factories\CompanyContactFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyContact filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyContact newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyContact newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyContact onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyContact paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyContact query()
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyContact simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyContact whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyContact whereCId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyContact whereCellPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyContact whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyContact whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyContact whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyContact whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyContact whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyContact whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyContact whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyContact wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyContact whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyContact whereWebsite($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyContact withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|CompanyContact withoutTrashed()
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Companycontact',
    description: 'CompanyContact model'
)]
class CompanyContact extends Model
{

    use HasFactory;
    use SoftDeletes;use Filterable;
    protected $table = 'company_contacts';
    protected $primaryKey = 'c_id';


    protected $casts = [
        'company_id' => 'int'
    ];

    protected $fillable = [
        'company_id',
        'name',
        'email',
        'website',
        'phone',
        'cell_phone'
    ];

	public function company()
	{
		return $this->belongsTo(Company::class);
	}
}
