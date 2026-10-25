<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ImagesCount
 *
 * @property int|null $company_id
 * @property int|null $product_id
 * @property int $cImg
 * @package App\Models
 * @method static \Database\Factories\ImagesCountFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|ImagesCount filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|ImagesCount newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ImagesCount newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ImagesCount paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|ImagesCount query()
 * @method static \Illuminate\Database\Eloquent\Builder|ImagesCount simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|ImagesCount whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|ImagesCount whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|ImagesCount whereLike($column, $value, $boolean = 'and')
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Imagescount',
    description: 'ImagesCount model'
)]
class ImagesCount extends Model
{

    use HasFactory;
    use Filterable;

    protected $table = 'images_count';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'company_id' => 'int',
        'product_id' => 'int',
        'cImg' => 'int'
    ];

    protected $fillable = [
        'company_id',
        'product_id',
        'cImg'
    ];
}
