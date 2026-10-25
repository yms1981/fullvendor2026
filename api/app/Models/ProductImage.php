<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * Class ProductImage
 *
 * @property int $img_id
 * @property int|null $product_id
 * @property Carbon|null $created
 * @property Carbon $updated
 * @property int|null $company_id
 * @property int|null $img_order
 * @property Company|null $company
 * @property Product|null $product
 * @package App\Models
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @method static \Database\Factories\ProductImageFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|ProductImage filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductImage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProductImage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProductImage onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|ProductImage paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductImage query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProductImage simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductImage whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|ProductImage whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductImage whereComprimed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductImage whereCreated($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductImage whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductImage whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductImage whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|ProductImage whereIdKor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductImage whereImageBlob($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductImage whereImageStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductImage whereImages($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductImage whereImg($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductImage whereImgDefault($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductImage whereImgId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductImage whereImgOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductImage whereImgURL($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductImage whereLanguageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductImage whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|ProductImage whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductImage whereSImgURL($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductImage whereSku($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductImage whereUpdated($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductImage withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|ProductImage withoutTrashed()
 * @property-read Image|null $image
 * @property int|null $image_id
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductImage whereImageId($value)
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Productimage',
    description: 'ProductImage model'
)]
class ProductImage extends Model
{

    use HasFactory;
    use Filterable;

    use SoftDeletes;

    const CREATED_AT = 'created';
    const UPDATED_AT = 'updated';
    protected $table = 'product_images';
    protected $primaryKey = 'img_id';


    protected $casts = [
        'product_id' => 'int',
        'created' => 'datetime',
        'updated' => 'datetime',
        'company_id' => 'int',
        'img_order' => 'int',
    ];

    protected $fillable = [
        'product_id',
        'image_id',
        'img_order',
    ];

	public function company()
	{
		return $this->belongsTo(Company::class);
	}

	public function product()
	{
		return $this->belongsTo(Product::class, 'product_id','pro_id');
	}

    public function image () {
        return $this->belongsTo(Image::class, 'image_id', 'uuid');
    }





}
