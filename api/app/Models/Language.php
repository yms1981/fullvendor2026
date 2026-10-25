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
 * Class Language
 *
 * @property int $language_id
 * @property string|null $name
 * @property string|null $iso_639_1
 * @property Carbon|null $created_at
 * @property int $status
 * @property string|null $type
 * @property int|null $default
 * @property Collection|Category[] $categories
 * @property Collection|Company[] $companies
 * @property Collection|CustomerGroup[] $customer_groups
 * @property Collection|Customer[] $customers
 * @property Collection|FormatDate[] $format_dates
 * @property Collection|Order[] $orders
 * @property Collection|ProductImage[] $product_images
 * @property Collection|Product[] $products
 * @property Collection|TermsOfSale[] $terms_of_sales
 * @property WordersList $worders_list
 * @package App\Models
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read int|null $categories_count
 * @property-read int|null $companies_count
 * @property-read int|null $customer_groups_count
 * @property-read int|null $customers_count
 * @property-read int|null $format_dates_count
 * @property-read int|null $orders_count
 * @property-read int|null $product_images_count
 * @property-read int|null $products_count
 * @property-read int|null $terms_of_sales_count
 * @property-read int|null $worders_list_count
 * @method static \Database\Factories\LanguageFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Language filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Language newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Language newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Language onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Language paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Language query()
 * @method static \Illuminate\Database\Eloquent\Builder|Language simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Language whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Language whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Language whereDefault($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Language whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Language whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Language whereIso6391($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Language whereLanguageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Language whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Language whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Language whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Language whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Language whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Language withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Language withoutTrashed()
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Language',
    description: 'Language model'
)]
class Language extends Model
{

    use HasFactory;
    use Filterable;

    use SoftDeletes;protected $table = 'languages';
    protected $primaryKey = 'language_id';


    protected $casts = [
        'status' => 'int',
        'default' => 'int'
    ];

    protected $fillable = [
        'name',
        'iso_639_1',
        'status',
        'type',
        'default'
    ];

	public function categories()
	{
		return $this->hasMany(Category::class);
	}

	public function companies()
	{
		return $this->hasMany(Company::class);
	}

	public function customer_groups()
	{
		return $this->hasMany(CustomerGroup::class);
	}

	public function customers()
	{
		return $this->hasMany(Customer::class);
	}

	public function format_dates()
	{
		return $this->hasMany(FormatDate::class);
	}

	public function orders()
	{
		return $this->hasMany(Order::class);
	}

	public function product_images()
	{
		return $this->hasMany(ProductImage::class);
	}

	public function products()
	{
		return $this->hasMany(Product::class);
	}

	public function terms_of_sales()
	{
		return $this->hasMany(TermsOfSale::class);
	}

	public function worders_list()
	{
		return $this->hasMany(WordersList::class);
	}
}
