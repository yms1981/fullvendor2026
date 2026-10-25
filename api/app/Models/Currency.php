<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use App\Support\CurrencySchemaHelper;
use Carbon\Carbon;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Currency
 *
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string $symbol
 * @property array $tags
 * @property Collection|Company[] $companies
 * @package App\Models
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read int|null $companies_count
 * @method static \Database\Factories\CurrencyFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Currency filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Currency newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Currency newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Currency onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Currency paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Currency query()
 * @method static \Illuminate\Database\Eloquent\Builder|Currency simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Currency whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Currency whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Currency whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Currency whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Currency whereCreated($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Currency whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Currency whereCurrencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Currency whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Currency whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Currency whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Currency whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Currency whereSymbol($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Currency whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Currency withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Currency withoutTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Currency whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Currency whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Currency whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Currency whereTags($value)
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Currency',
    description: 'Currency model'
)]
class Currency extends Model
{

    use HasFactory;
    use Filterable;

    use SoftDeletes;

    protected $table = 'currency';

    protected $casts = [
        'tags' => 'array',
    ];

    protected $fillable = [
        'name',
        'code',
        'symbol',
        'tags',
        'company_id',
        'status',
        'country',
        'currency',
    ];

    public function getKeyName()
    {
        return CurrencySchemaHelper::primaryKeyColumn();
    }

    public function companies()
    {
        return $this->hasMany(Company::class, 'currency', CurrencySchemaHelper::companiesCurrencyOwnerKey());
    }

    public static function default()
    {
        return self::first(); //cual deberia ser el default?
    }
}
