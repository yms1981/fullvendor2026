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
 * Class CurrencyFormat
 *
 * @property string $CurrencyName
 * @property string $CurrencyCode
 * @property string $CurrencyFormat
 * @property string|null $currOutput
 * @property string|null $CodeRegional
 * @property int $format_id
 * @property int|null $order
 * @package App\Models
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @method static \Database\Factories\CurrencyFormatFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|CurrencyFormat filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|CurrencyFormat newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CurrencyFormat newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CurrencyFormat onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|CurrencyFormat paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|CurrencyFormat query()
 * @method static \Illuminate\Database\Eloquent\Builder|CurrencyFormat simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|CurrencyFormat whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|CurrencyFormat whereCodeRegional($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CurrencyFormat whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CurrencyFormat whereCurrOutput($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CurrencyFormat whereCurrencyCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CurrencyFormat whereCurrencyFormat($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CurrencyFormat whereCurrencyName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CurrencyFormat whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CurrencyFormat whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|CurrencyFormat whereFormatId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CurrencyFormat whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|CurrencyFormat whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CurrencyFormat whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CurrencyFormat withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|CurrencyFormat withoutTrashed()
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Currencyformat',
    description: 'CurrencyFormat model'
)]
class CurrencyFormat extends Model
{

    use HasFactory;
    use Filterable;

    use SoftDeletes;protected $table = 'currency_formats';
    protected $primaryKey = 'format_id';


    protected $casts = [
        'order' => 'int'
    ];

    protected $fillable = [
        'CurrencyName',
        'CurrencyCode',
        'CurrencyFormat',
        'currOutput',
        'CodeRegional',
        'order'
    ];
}
