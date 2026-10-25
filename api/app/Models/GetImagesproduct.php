<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class GetImagesproduct
 *
 * @property int|null $company_id
 * @property int|null $product_id
 * @property int $qty_images
 * @property string|null $group_images
 * @package App\Models
 * @method static \Database\Factories\GetImagesproductFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|GetImagesproduct filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|GetImagesproduct newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GetImagesproduct newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GetImagesproduct paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|GetImagesproduct query()
 * @method static \Illuminate\Database\Eloquent\Builder|GetImagesproduct simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|GetImagesproduct whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|GetImagesproduct whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|GetImagesproduct whereLike($column, $value, $boolean = 'and')
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Getimagesproduct',
    description: 'GetImagesproduct model'
)]
class GetImagesproduct extends Model
{

    use HasFactory;
    use Filterable;

    protected $table = 'get_imagesproducts';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'company_id' => 'int',
        'product_id' => 'int',
        'qty_images' => 'int'
    ];

    protected $fillable = [
        'company_id',
        'product_id',
        'qty_images',
        'group_images'
    ];
}
