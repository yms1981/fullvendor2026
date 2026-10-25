<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ImagesDefault
 *
 * @property string|null $images
 * @property int $pro_id
 * @package App\Models
 * @method static \Database\Factories\ImagesDefaultFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|ImagesDefault filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|ImagesDefault newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ImagesDefault newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ImagesDefault paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|ImagesDefault query()
 * @method static \Illuminate\Database\Eloquent\Builder|ImagesDefault simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|ImagesDefault whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|ImagesDefault whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|ImagesDefault whereLike($column, $value, $boolean = 'and')
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Imagesdefault',
    description: 'ImagesDefault model'
)]
class ImagesDefault extends Model
{

    use HasFactory;
    use Filterable;

    protected $table = 'images_default';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'pro_id' => 'int'
    ];

    protected $fillable = [
        'images',
        'pro_id'
    ];
}
