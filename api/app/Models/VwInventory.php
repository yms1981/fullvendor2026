<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class VwInventory
 *
 * @property int|null $company_id
 * @property int $product_id
 * @property string|null $sku
 * @property string|null $name
 * @property float|null $Inventory
 * @property float|null $sale_price
 * @property float|null $amount_inventory
 * @package App\Models
 * @method static \Illuminate\Database\Eloquent\Builder|VwInventory filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|VwInventory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|VwInventory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|VwInventory paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|VwInventory query()
 * @method static \Illuminate\Database\Eloquent\Builder|VwInventory simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|VwInventory whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|VwInventory whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|VwInventory whereLike($column, $value, $boolean = 'and')
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Vwinventory',
    description: 'VwInventory model'
)]
class VwInventory extends Model
{

    use HasFactory;
    use Filterable;

    protected $table = 'vw_inventory';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'company_id' => 'int',
        'product_id' => 'int',
        'Inventory' => 'float',
        'sale_price' => 'float',
        'amount_inventory' => 'float'
    ];

    protected $fillable = [
        'company_id',
        'product_id',
        'sku',
        'name',
        'Inventory',
        'sale_price',
        'amount_inventory'
    ];
}
