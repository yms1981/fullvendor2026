<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OpenApi\Attributes as OA;

/**
 * Class Unit
 *
 * @property string $unit
 * @property string $description
 * @property int $company_id
 * @property int|null $status
 * @property Carbon|null $created
 * @property int $unit_id
 * @property int|null $decimales
 * @property string|null $formato
 * @property Company $company
 * @package App\Models
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Database\Factories\UnitFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Unit filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Unit newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Unit newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Unit paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Unit query()
 * @method static \Illuminate\Database\Eloquent\Builder|Unit simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Unit whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Unit whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Unit whereCreated($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Unit whereDecimales($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Unit whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Unit whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Unit whereFormato($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Unit whereIdKor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Unit whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Unit whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Unit whereUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Unit whereUnitId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Unit whereUpdatedAt($value)
 * @property-read bool $is_default
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Product> $products
 * @property-read int|null $products_count
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'UnitModel',
    description: 'Unit model'
)]
class Unit extends Model
{

    use HasFactory;
    use Filterable;

    const CREATED_AT = 'created';
    protected $table = 'units';
    protected $primaryKey = 'unit_id';


    protected $casts = [
        'unit' => 'string',
        'company_id' => 'int',
        'status' => 'int',
        'created' => 'datetime',
        'unit_id' => 'int',
        'decimales' => 'int',
    ];

    protected $fillable = [
        'company_id',
        'unit',
        'description',
        'status',
        'decimales',
        'formato',
    ];

    protected $appends = [
        'isDefault'
    ];

    protected $with = ['company'];

    public function company()
	{
		return $this->belongsTo(Company::class, 'company_id','company_id');
	}

    public function products()
    {
        return $this->hasMany(Product::class, 'unit_id');
    }

    public function getIsDefaultAttribute(): bool
    {
        $company = $this->relationLoaded('company') ? $this->company : $this->company()->first();

        return $company !== null && (int) $company->default_unit_id === (int) $this->getKey();
    }
}
