<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class DictionaryGp
 *
 * @property int $id
 * @property string|null $eng
 * @property string|null $esp
 * @property string|null $arg
 * @package App\Models
 * @method static \Database\Factories\DictionaryGpFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|DictionaryGp filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|DictionaryGp newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DictionaryGp newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DictionaryGp paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|DictionaryGp query()
 * @method static \Illuminate\Database\Eloquent\Builder|DictionaryGp simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|DictionaryGp whereArg($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DictionaryGp whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|DictionaryGp whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|DictionaryGp whereEng($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DictionaryGp whereEsp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DictionaryGp whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DictionaryGp whereLike($column, $value, $boolean = 'and')
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Dictionarygp',
    description: 'DictionaryGp model'
)]
class DictionaryGp extends Model
{

    use HasFactory;
    use Filterable;

    protected $table = 'dictionary_gps';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'id' => 'int'
    ];

    protected $fillable = [
        'eng',
        'esp',
        'arg'
    ];
}
