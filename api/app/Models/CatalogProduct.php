<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class CatalogProduct
 *
 * @property int $catalog_id
 * @property int product_id
 * @package App\Models
 * @property int $product_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogProduct newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogProduct newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogProduct onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogProduct query()
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogProduct whereCatalogId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogProduct whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogProduct whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogProduct whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogProduct whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogProduct withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogProduct withoutTrashed()
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Catalogproduct',
    description: 'CatalogProduct model'
)]
class CatalogProduct extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'catalog_product';

    protected $casts = [
        'catalog_id' => 'int',
        'product_id' => 'int'
    ];

    protected $fillable = [
        'catalog_id',
        'product_id'
    ];
}
