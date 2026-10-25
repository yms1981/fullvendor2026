<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class CatalogCategory
 *
 * @property int $catalog_id
 * @property int $category_id
 * @package App\Models
 * @property int $order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCategory onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCategory whereCatalogId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCategory whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCategory whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCategory whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCategory whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCategory withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|CatalogCategory withoutTrashed()
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Catalogcategory',
    description: 'CatalogCategory model'
)]
class CatalogCategory extends Model
{
    use SoftDeletes;

	protected $table = 'catalog_category';
	public $incrementing = false;

	protected $casts = [
		'catalog_id' => 'int',
		'category_id' => 'int',
        'order' => 'int'
	];

    protected $fillable = [
        'category_id',
        'catalog_id',
        'order'
    ];

    public function makeFillableCreatedAt()
    {
        if (!in_array('created_at', $this->fillable)) {
            $this->fillable[] = 'created_at';
        }
        return $this;
    }

}
