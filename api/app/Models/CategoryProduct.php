<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class CategoryProduct
 *
 * @property int $category_id
 * @property int $product_id
 * @package App\Models
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder|CategoryProduct newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CategoryProduct newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CategoryProduct onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|CategoryProduct query()
 * @method static \Illuminate\Database\Eloquent\Builder|CategoryProduct whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CategoryProduct whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CategoryProduct whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CategoryProduct whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CategoryProduct whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CategoryProduct withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|CategoryProduct withoutTrashed()
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Categoryproduct',
    description: 'CategoryProduct model'
)]
class CategoryProduct extends Model
{
    use SoftDeletes;

    protected $table = 'category_product';
	public $incrementing = false;

	protected $casts = [
		'category_id' => 'int',
		'product_id' => 'int'
	];

    protected $fillable = [
        'category_id',
        'product_id'
    ];
}
