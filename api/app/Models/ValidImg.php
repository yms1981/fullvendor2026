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
 * Class ValidImg
 *
 * @property int|null $pro_id
 * @property int $img_id
 * @property int|null $product_id
 * @property int|null $language_id
 * @property string|null $images
 * @property Carbon|null $created
 * @property Carbon $updated
 * @property string|null $Img
 * @property string|null $sImgURL
 * @property string|null $ImgURL
 * @property int|null $id_kor
 * @property string|null $Description
 * @property int|null $company_id
 * @property string|null $sku
 * @property bool|null $img_default
 * @package App\Models
 * @method static \Illuminate\Database\Eloquent\Builder|ValidImg filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|ValidImg newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ValidImg newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ValidImg paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|ValidImg query()
 * @method static \Illuminate\Database\Eloquent\Builder|ValidImg simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|ValidImg whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|ValidImg whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ValidImg whereCreated($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ValidImg whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ValidImg whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|ValidImg whereIdKor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ValidImg whereImages($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ValidImg whereImg($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ValidImg whereImgDefault($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ValidImg whereImgId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ValidImg whereImgURL($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ValidImg whereLanguageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ValidImg whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|ValidImg whereProId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ValidImg whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ValidImg whereSImgURL($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ValidImg whereSku($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ValidImg whereUpdated($value)
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Validimg',
    description: 'ValidImg model'
)]
class ValidImg extends Model
{

    use HasFactory;
    use Filterable;

    protected $table = 'Valid_Img';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'pro_id' => 'int',
        'img_id' => 'int',
        'product_id' => 'int',
        'language_id' => 'int',
        'created' => 'datetime',
        'updated' => 'datetime',
        'id_kor' => 'int',
        'company_id' => 'int',
        'img_default' => 'bool'
    ];

    protected $fillable = [
        'pro_id',
        'img_id',
        'product_id',
        'language_id',
        'images',
        'created',
        'updated',
        'Img',
        'sImgURL',
        'ImgURL',
        'id_kor',
        'Description',
        'company_id',
        'sku',
        'img_default'
    ];
}
