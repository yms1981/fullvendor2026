<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ImagesProduct
 *
 * @property int|null $img_id
 * @property string|null $images
 * @property Carbon|null $created
 * @property int $pro_id
 * @property int|null $company_id
 * @property string|null $imageBlob
 * @package App\Models
 * @method static \Database\Factories\ImagesProductFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|ImagesProduct filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|ImagesProduct newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ImagesProduct newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ImagesProduct paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|ImagesProduct query()
 * @method static \Illuminate\Database\Eloquent\Builder|ImagesProduct simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|ImagesProduct whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|ImagesProduct whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ImagesProduct whereCreated($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ImagesProduct whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|ImagesProduct whereImageBlob($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ImagesProduct whereImages($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ImagesProduct whereImgId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ImagesProduct whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|ImagesProduct whereProId($value)
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Imagesproduct',
    description: 'ImagesProduct model'
)]
class ImagesProduct extends Model
{

    use HasFactory;
    use Filterable;

    protected $table = 'images_products';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'img_id' => 'int',
        'created' => 'datetime',
        'pro_id' => 'int',
        'company_id' => 'int'
    ];

    protected $fillable = [
        'img_id',
        'images',
        'created',
        'pro_id',
        'company_id',
        'imageBlob'
    ];
}
