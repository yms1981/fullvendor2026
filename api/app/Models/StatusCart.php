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
 * Class StatusCart
 *
 * @property int $id_status
 * @property string|null $name_spa
 * @property string|null $name_eng
 * @property string|null $color
 * @property string|null $cod
 * @property string|null $class
 * @property string|null $selected_default
 * @property int|null $allow_toremove
 * @property int|null $multiple_cart
 * @property int|null $nosubmit_order
 * @package App\Models
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CatalogCart> $catalogCarts
 * @property-read int|null $catalog_carts_count
 * @method static \Database\Factories\StatusCartFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|StatusCart filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|StatusCart newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|StatusCart newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|StatusCart onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|StatusCart paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|StatusCart query()
 * @method static \Illuminate\Database\Eloquent\Builder|StatusCart simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|StatusCart whereAllowToremove($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StatusCart whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|StatusCart whereClass($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StatusCart whereCod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StatusCart whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StatusCart whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StatusCart whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StatusCart whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|StatusCart whereIdStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StatusCart whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|StatusCart whereMultipleCart($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StatusCart whereNameEng($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StatusCart whereNameSpa($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StatusCart whereNosubmitOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StatusCart whereSelectedDefault($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StatusCart whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StatusCart withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|StatusCart withoutTrashed()
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Statuscart',
    description: 'StatusCart model'
)]
class StatusCart extends Model
{

    use HasFactory;
    use Filterable;

    use SoftDeletes;protected $table = 'status_cart';
    protected $primaryKey = 'id_status';


    protected $casts = [
        'allow_toremove' => 'int',
        'multiple_cart' => 'int',
        'nosubmit_order' => 'int'
    ];

    protected $fillable = [
        'name_spa',
        'name_eng',
        'color',
        'cod',
        'class',
        'selected_default',
        'allow_toremove',
        'multiple_cart',
        'nosubmit_order'
    ];

    public function catalogCarts()
    {
        return $this->hasMany(CatalogCart::class, 'status_cart', 'id_status');
    }
}
