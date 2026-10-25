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
 * Class TermsOfSale
 *
 * @property int $term_id
 * @property int|null $language_id
 * @property int|null $company_id
 * @property int|null $user_id
 * @property string|null $name
 * @property Carbon|null $created_at
 * @property int $term_status
 * @property int|null $default
 * @property Company|null $company
 * @property Language|null $language
 * @property User|null $user
 * @property Collection|Customer[] $customers
 * @package App\Models
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read int|null $customers_count
 * @method static \Database\Factories\TermsOfSaleFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|TermsOfSale filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|TermsOfSale newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TermsOfSale newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TermsOfSale onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|TermsOfSale paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|TermsOfSale query()
 * @method static \Illuminate\Database\Eloquent\Builder|TermsOfSale simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|TermsOfSale whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|TermsOfSale whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TermsOfSale whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TermsOfSale whereDefault($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TermsOfSale whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TermsOfSale whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|TermsOfSale whereLanguageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TermsOfSale whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|TermsOfSale whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TermsOfSale whereTermId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TermsOfSale whereTermStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TermsOfSale whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TermsOfSale whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TermsOfSale withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|TermsOfSale withoutTrashed()
 * @property-read bool $is_default
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Termsofsale',
    description: 'TermsOfSale model'
)]
class TermsOfSale extends Model
{

    use HasFactory;
    use Filterable;

    use SoftDeletes;protected $table = 'terms_of_sales';
    protected $primaryKey = 'term_id';


    protected $casts = [
        'company_id' => 'int',
        'name' => 'string',
        'term_status' => 'int',
    ];

    protected $fillable = [
        'company_id',
        'name',
        'term_status',
    ];

    protected $appends = [
        'isDefault'
    ];

    protected $with = ['company'];

	public function company()
	{
		return $this->belongsTo(Company::class,  'company_id', 'company_id');
	}

	public function customers()
	{
		return $this->hasMany(Customer::class, 'term_id');
	}

    public function getIsDefaultAttribute(): bool
    {
        $company = $this->relationLoaded('company') ? $this->company : $this->company()->first();

        return $company !== null && (int) $company->default_term_id === (int) $this->getKey();
    }
}
