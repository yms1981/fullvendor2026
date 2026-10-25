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
 * Class FormatDate
 *
 * @property int $id
 * @property string|null $format_php
 * @property string|null $format_js
 * @property int|null $language_id
 * @property string|null $date_firstmonth
 * @property string|null $default
 * @property string|null $month_year
 * @property Language|null $language
 * @package App\Models
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @method static \Database\Factories\FormatDateFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|FormatDate filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|FormatDate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FormatDate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FormatDate onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|FormatDate paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|FormatDate query()
 * @method static \Illuminate\Database\Eloquent\Builder|FormatDate simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|FormatDate whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|FormatDate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FormatDate whereDateFirstmonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FormatDate whereDefault($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FormatDate whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FormatDate whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|FormatDate whereFormatJs($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FormatDate whereFormatPhp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FormatDate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FormatDate whereLanguageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FormatDate whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|FormatDate whereMonthYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FormatDate whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FormatDate withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|FormatDate withoutTrashed()
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Formatdate',
    description: 'FormatDate model'
)]
class FormatDate extends Model
{

    use HasFactory;
    use Filterable;

    use SoftDeletes;
    public $timestamps = false;

    protected $table = 'format_date';protected $casts = [
        'language_id' => 'int'
    ];

    protected $fillable = [
        'format_php',
        'format_js',
        'language_id',
        'date_firstmonth',
        'default',
        'month_year'
    ];

	public function language()
	{
		return $this->belongsTo(Language::class);
	}
}
