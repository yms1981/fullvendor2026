<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Category
 *
 * @property int $cat_id
 * @property int $company_id
 * @property string|null $category_name
 * @property string|null $image
 * @property int $category_status
 * @property Carbon|null $category_created_at
 * @property int|null $category_default
 * @property Company $company
 * @property Language|null $language
 * @property Collection|OrderCategory[] $order_categories
 * @package App\Models
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read Collection<int, \App\Models\Catalog> $catalogs
 * @property-read int|null $catalogs_count
 * @property-read int|null $order_categories_count
 * @property-read Collection<int, \App\Models\Product> $products
 * @property-read int|null $products_count
 * @method static \Database\Factories\CategoryFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Category filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Category newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Category newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Category onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Category paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Category query()
 * @method static \Illuminate\Database\Eloquent\Builder|Category simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Category whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Category whereCatId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Category whereCategoryCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Category whereCategoryDefault($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Category whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Category whereCategoryName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Category whereCategoryStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Category whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Category whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Category whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Category whereIdKor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Category whereImage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Category whereLanguageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Category whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Category whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Category withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Category withoutTrashed()
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Category',
    description: 'Category model'
)]
class Category extends Model
{
    use SoftDeletes;
    use HasFactory;
    use Filterable;

    const CREATED_AT = 'category_created_at';
    protected $table = 'categories';
    protected $primaryKey = 'cat_id';


    protected $casts = [
        'company_id' => 'int',
        'category_status' => 'int',
        'category_created_at' => 'datetime',
        'category_default' => 'int'
    ];

    protected $fillable = [
        'company_id',
        'category_name',
        'image',
        'category_status',
        'category_default'
    ];

	public function company()
	{
		return $this->belongsTo(Company::class);
	}

	public function order_categories()
	{
		return $this->hasMany(OrderCategory::class);
	}

    public function products(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'category_product', 'category_id','product_id');
    }

    public function catalogs(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Catalog::class, 'catalog_category', 'category_id','catalog_id');
    }

    /**
     * Relación con el archivo de imagen.
     * IMPORTANTE: no llamar a esta relación "image" — choca con la FK `categories.image`
     * (UUID string). Si se llaman igual, $category->image devuelve el UUID y en toArray()
     * la relación puede dejar el listado sin UUID usable.
     */
    public function imageRecord(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Image::class, 'image', 'uuid');
    }

    /**
     * Alias legacy para with('image') / relations=image.
     * Preferir imageRecord() en código nuevo.
     */
    public function image(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->imageRecord();
    }

    public function groups(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CategoryGroup::class, 'category_id', 'cat_id')
            ->orderBy('sort_order')
            ->orderBy('group_name');
    }

    public function toArray()
    {
        $array = parent::toArray();
        $fk = $this->attributes['image'] ?? null;
        if (is_string($fk) && $fk !== '') {
            if (preg_match('/^([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})/i', $fk, $m)) {
                $array['image_uuid'] = $m[1];
            } else {
                $array['image_uuid'] = $fk;
            }
        }

        // Si la relación "image" pisó el FK con null/objeto, garantizar image_uuid para el MVC.
        if (empty($array['image_uuid']) && is_array($array['image'] ?? null) && ! empty($array['image']['uuid'])) {
            $array['image_uuid'] = (string) $array['image']['uuid'];
        }

        // Relación image cargada pero sin registro: no dejar image=null (rompe el listado).
        if (($array['image'] ?? null) === null && ! empty($array['image_uuid'])) {
            $array['image'] = $array['image_uuid'];
        }

        return $array;
    }
}
