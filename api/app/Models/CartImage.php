<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class CartImage
 *
 * @property int|null $company_id
 * @property int $img_id
 * @property int $pro_id
 * @package App\Models
 * @method static \Database\Factories\CartImageFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|CartImage filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|CartImage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CartImage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CartImage paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|CartImage query()
 * @method static \Illuminate\Database\Eloquent\Builder|CartImage simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|CartImage whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|CartImage whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CartImage whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|CartImage whereImgId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CartImage whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|CartImage whereProId($value)
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Cartimage',
    description: 'CartImage model'
)]
class CartImage extends Model
{

    use HasFactory;
    use Filterable;

    protected $table = 'cartImages';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'company_id' => 'int',
        'img_id' => 'int',
        'pro_id' => 'int'
    ];

    protected $fillable = [
        'company_id',
        'img_id',
        'pro_id'
    ];
}
