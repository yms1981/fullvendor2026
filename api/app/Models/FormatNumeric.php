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
 * Class FormatNumeric
 *
 * @property int $format_id
 * @property string|null $numeric_format
 * @property string|null $radixPoint
 * @property string|null $groupSeparator
 * @property int|null $digits
 * @property int|null $default
 * @property string|null $code_regional
 * @package App\Models
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @method static \Database\Factories\FormatNumericFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|FormatNumeric filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|FormatNumeric newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FormatNumeric newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FormatNumeric onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|FormatNumeric paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|FormatNumeric query()
 * @method static \Illuminate\Database\Eloquent\Builder|FormatNumeric simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|FormatNumeric whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|FormatNumeric whereCodeRegional($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FormatNumeric whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FormatNumeric whereDefault($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FormatNumeric whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FormatNumeric whereDigits($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FormatNumeric whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|FormatNumeric whereFormatId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FormatNumeric whereGroupSeparator($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FormatNumeric whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|FormatNumeric whereNumericFormat($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FormatNumeric whereRadixPoint($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FormatNumeric whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FormatNumeric withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|FormatNumeric withoutTrashed()
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Formatnumeric',
    description: 'FormatNumeric model'
)]
class FormatNumeric extends Model
{

    use HasFactory;
    use Filterable;
    use SoftDeletes;

    protected $table = 'format_numeric';
    protected $primaryKey = 'format_id';


    protected $casts = [
        'digits' => 'int',
        'default' => 'int'
    ];

    protected $fillable = [
        'numeric_format',
        'radixPoint',
        'groupSeparator',
        'digits',
        'default',
        'code_regional'
    ];

    public static function default()
    {
        return self::where('default', true)->first();
    }

    public function formatNumber($value): string
    {
        return number_format($value, $this->digits, $this->radixPoint, $this->groupSeparator);
    }
}
