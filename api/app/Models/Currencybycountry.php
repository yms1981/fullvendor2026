<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Currencybycountry
 *
 * @property int $id
 * @property string|null $GroupID
 * @property string|null $Name
 * @property string|null $Contry
 * @property string|null $Code
 * @property string|null $Symbol
 * @property string|null $GroupIdspa
 * @property string|null $Namespa
 * @property string|null $default
 * @package App\Models
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @method static \Database\Factories\CurrencybycountryFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Currencybycountry filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Currencybycountry newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Currencybycountry newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Currencybycountry onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Currencybycountry paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Currencybycountry query()
 * @method static \Illuminate\Database\Eloquent\Builder|Currencybycountry simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Currencybycountry whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Currencybycountry whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Currencybycountry whereContry($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Currencybycountry whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Currencybycountry whereDefault($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Currencybycountry whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Currencybycountry whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Currencybycountry whereGroupID($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Currencybycountry whereGroupIdspa($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Currencybycountry whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Currencybycountry whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Currencybycountry whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Currencybycountry whereNamespa($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Currencybycountry whereSymbol($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Currencybycountry whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Currencybycountry withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Currencybycountry withoutTrashed()
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Currencybycountry',
    description: 'Currencybycountry model'
)]
class Currencybycountry extends Model
{

    use HasFactory;
    use Filterable;

    protected $table = 'currencybycountries';
    public $timestamps = false;

    protected $fillable = [
        'GroupID',
        'Name',
        'Contry',
        'Code',
        'Symbol',
        'GroupIdspa',
        'Namespa',
        'default'
    ];
}
