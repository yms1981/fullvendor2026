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
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Catalog
 *
 * @property int $catalog_id
 * @property string $catalog_name
 * @property int $company_id
 * @property string $cat_id
 * @property string|null $product_name
 * @property int $status
 * @property Carbon|null $created
 * @property string|null $customer_emails
 * @property Carbon|null $email_sent_on
 * @property string|null $attach
 * @property int $dayexpired
 * @property int|null $default_catalog
 * @property string|null $cabeza_pagina
 * @property string|null $deleted_at
 * @property Company $company
 * @property Collection|CatalogCart[] $catalog_carts
 * @property Collection|CatalogSentMail[] $catalog_sent_mails
 * @property Collection|OrderCategory[] $order_categories
 * @property Collection|Order[] $orders
 * @package App\Models
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read int|null $catalog_carts_count
 * @property-read int|null $catalog_sent_mails_count
 * @property-read Collection<int, \App\Models\Category> $categories
 * @property-read int|null $categories_count
 * @property-read int|null $order_categories_count
 * @property-read int|null $orders_count
 * @property-read Collection<int, \App\Models\Product> $products
 * @property-read int|null $products_count
 * @method static \Database\Factories\CatalogFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Catalog filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Catalog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Catalog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Catalog onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Catalog paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Catalog query()
 * @method static \Illuminate\Database\Eloquent\Builder|Catalog simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Catalog whereAttach($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Catalog whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Catalog whereCabezaPagina($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Catalog whereCatId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Catalog whereCatalogId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Catalog whereCatalogName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Catalog whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Catalog whereCreated($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Catalog whereCustomerEmails($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Catalog whereDayexpired($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Catalog whereDefaultCatalog($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Catalog whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Catalog whereEmailSentOn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Catalog whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Catalog whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Catalog whereProductName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Catalog whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Catalog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Catalog withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Catalog withoutTrashed()
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Catalog',
    description: 'Catalog model'
)]
class Catalog extends Model
{

    use HasFactory;
    use Filterable;
    use SoftDeletes;

    const CREATED_AT = 'created';
    const UPDATED_AT = 'updated_at';
    protected $table = 'catalog';
    protected $primaryKey = 'catalog_id';


    protected $casts = [
        'company_id' => 'int',
        'status' => 'int',
        'created' => 'datetime',
        'email_sent_on' => 'datetime',
        'dayexpired' => 'int',
        'default_catalog' => 'int'
    ];

    protected $attributes = [
        'cat_id' => '',
    ];

    protected $fillable = [
        'catalog_name',
        'company_id',
        'cat_id',
        'product_name',
        'status',
        'created',
        'customer_emails',
        'email_sent_on',
        'attach',
        'dayexpired',
        'default_catalog',
        'cabeza_pagina',
        'logo',
    ];

	public function company()
	{
		return $this->belongsTo(Company::class, 'company_id');
	}

	public function logoRecord()
	{
		return $this->belongsTo(Image::class, 'logo', 'uuid');
	}

	public function catalog_carts()
	{
		return $this->hasMany(CatalogCart::class);
	}

	public function catalog_sent_mails()
	{
		return $this->hasMany(CatalogSentMail::class);
	}

	public function order_categories()
	{
		return $this->hasMany(OrderCategory::class);
	}

	public function orders()
	{
		return $this->hasMany(Order::class);
	}

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'catalog_category', 'catalog_id','category_id')
            ->withPivot('order');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'catalog_product', 'catalog_id','product_id');
    }
}
