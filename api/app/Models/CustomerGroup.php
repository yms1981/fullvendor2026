<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use App\Enums\CustomerGroupTypes;
use Carbon\Carbon;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class CustomerGroup
 *
 * @property int $group_id
 * @property int|null $company_id
 * @property string|null $name
 * @property string|null $percentage_on_price
 * @property float|null     $percent_price_amount
 * @property Carbon|null $created_at
 * @property int $group_status
 * @property Company|null $company
 * @property Language|null $language
 * @property User|null $user
 * @property Collection|Customer[] $customers
 * @package App\Models
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read int|null $customers_count
 * @method static \Database\Factories\CustomerGroupFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|CustomerGroup filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|CustomerGroup newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CustomerGroup newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CustomerGroup onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|CustomerGroup paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|CustomerGroup query()
 * @method static \Illuminate\Database\Eloquent\Builder|CustomerGroup simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|CustomerGroup whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|CustomerGroup whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CustomerGroup whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CustomerGroup whereDefault($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CustomerGroup whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CustomerGroup whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|CustomerGroup whereGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CustomerGroup whereGroupStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CustomerGroup whereIdKor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CustomerGroup whereLanguageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CustomerGroup whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|CustomerGroup whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CustomerGroup wherePercentPriceAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CustomerGroup wherePercentageOnPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CustomerGroup whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CustomerGroup whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CustomerGroup withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|CustomerGroup withoutTrashed()
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Customergroup',
    description: 'CustomerGroup model'
)]
class CustomerGroup extends Model
{

    use HasFactory;
    use Filterable;
    use SoftDeletes;

    protected $table = 'customer_groups';
    protected $primaryKey = 'group_id';

    protected $casts = [
        'company_id' => 'int',
        'percent_price_amount' => 'float',
        'group_status' => 'int',
    ];

    protected $fillable = [
        'company_id',
        'name',
        'percentage_on_price',
        'percent_price_amount',
        'group_status',
    ];

    protected $appends = [
        'isDefault'
    ];

	public function company()
	{
		return $this->belongsTo(Company::class, 'company_id', 'company_id');
	}

	public function user()
	{
		return $this->belongsTo(User::class);
	}

	public function customers()
	{
		return $this->hasMany(Customer::class, 'group_id');
	}

    public function isIncrease(): bool
    {
        return $this->percentage_on_price === CustomerGroupTypes::Increase->value;
    }

    public function isDecrease(): bool
    {
        return $this->percentage_on_price === CustomerGroupTypes::Decrease->value;
    }

    public function getIsDefaultAttribute(): bool
    {
        // Evitar N+1 en listados: solo evaluar si company ya está eager-loaded.
        if (! $this->relationLoaded('company')) {
            return false;
        }

        $company = $this->company;

        return $company !== null && (int) $company->default_customer_group_id === (int) $this->getKey();
    }
}
