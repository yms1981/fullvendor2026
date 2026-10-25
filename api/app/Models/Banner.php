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
 * Class Banner
 *
 * @property int $bannerId
 * @property string|null $bannerHeading
 * @property string|null $bannerDescription
 * @property string|null $bannerImage
 * @package App\Models
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @method static \Database\Factories\BannerFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Banner filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Banner newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Banner newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Banner onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Banner paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Banner query()
 * @method static \Illuminate\Database\Eloquent\Builder|Banner simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Banner whereBannerDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Banner whereBannerHeading($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Banner whereBannerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Banner whereBannerImage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Banner whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Banner whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Banner whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Banner whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Banner whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Banner whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Banner withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Banner withoutTrashed()
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Banner',
    description: 'Banner model'
)]
class Banner extends Model
{

    use HasFactory;
    use Filterable;
use SoftDeletes;
    protected $table = 'banner';
    protected $primaryKey = 'bannerId';


    protected $fillable = [
        'bannerHeading',
        'bannerDescription',
        'bannerImage'
    ];
}
