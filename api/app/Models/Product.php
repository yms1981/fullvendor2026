<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use App\Enums\OrderStatusCodes;
use Carbon\Carbon;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Product
 *
 * @property int $pro_id
 * @property int|null $product_id
 * @property int $language_id
 * @property int|null $company_id
 * @property int|null $unit_id
 * @property int|null $warehouse_user_id
 * @property string|null $sku
 * @property string|null $name
 * @property float $sale_price
 * @property string|null $category_id
 * @property string|null $barcode
 * @property string|null $barcode_images
 * @property string|null $tags
 * @property string|null $descriptions
 * @property array|null $specifications
 * @property float $stock
 * @property float $minimum_stock
 * @property int $notify_minimum_stock
 * @property string|null $currency_type
 * @property float $fob_price
 * @property float $purchase_price
 * @property string|null $locations
 * @property string|null $created_by
 * @property Carbon|null $created_on
 * @property Carbon $modified_on
 * @property int $status
 * @property string|null $Img
 * @property string|null $imgURL
 * @property string|null $simgURL
 * @property string|null $cat_id
 * @property string|null $cats_id
 * @property int|null $sell_with_inventory
 * @property int|null $image_updated
 * @property float $price_reduced
 * @property int|null $sem_red_hasta
 * @property int|null $sem_amarillo_desde
 * @property int|null $sem_amarillo_hasta
 * @property int|null $sem_verde_desde
 * @property int|null $sem_verde_hasta
 * @property Company|null $company
 * @property Language $language
 * @property WarehouseUser|null $warehouse_user
 * @property Collection|CatalogCart[] $catalog_carts
 * @property Collection|DeliveredOrder[] $delivered_orders
 * @property Collection|DetailCar[] $detail_cars
 * @property Collection|Inventory[] $inventories
 * @property Collection|OrderDetail[] $order_details
 * @property Collection|ProductImage[] $product_images
 * @package App\Models
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read int|null $catalog_carts_count
 * @property-read Collection<int, \App\Models\Catalog> $catalogs
 * @property-read int|null $catalogs_count
 * @property-read Collection<int, \App\Models\Category> $categories
 * @property-read int|null $categories_count
 * @property-read \App\Models\Currency|null $currency
 * @property-read \App\Models\Image|null $default_image
 * @property-read int|null $delivered_orders_count
 * @property-read int|null $detail_cars_count
 * @property-read int|null $inventories_count
 * @property-read int|null $order_details_count
 * @property-read int|null $product_images_count
 * @method static \Database\Factories\ProductFactory factory($count = null, $state = [])
 * @method static Builder|Product filter(array $input = [], $filter = null)
 * @method static Builder|Product newModelQuery()
 * @method static Builder|Product newQuery()
 * @method static Builder|Product onlyTrashed()
 * @method static Builder|Product paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static Builder|Product query()
 * @method static Builder|Product simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static Builder|Product whereBarcode($value)
 * @method static Builder|Product whereBarcodeImages($value)
 * @method static Builder|Product whereBeginsWith($column, $value, $boolean = 'and')
 * @method static Builder|Product whereCatId($value)
 * @method static Builder|Product whereCategoryId($value)
 * @method static Builder|Product whereCatsId($value)
 * @method static Builder|Product whereCompanyId($value)
 * @method static Builder|Product whereCreatedBy($value)
 * @method static Builder|Product whereCreatedOn($value)
 * @method static Builder|Product whereCurrencyType($value)
 * @method static Builder|Product whereDeletedAt($value)
 * @method static Builder|Product whereDescriptions($value)
 * @method static Builder|Product whereEndsWith($column, $value, $boolean = 'and')
 * @method static Builder|Product whereFobPrice($value)
 * @method static Builder|Product whereIdKor($value)
 * @method static Builder|Product whereImageUpdated($value)
 * @method static Builder|Product whereImg($value)
 * @method static Builder|Product whereImgURL($value)
 * @method static Builder|Product whereLanguageId($value)
 * @method static Builder|Product whereLike($column, $value, $boolean = 'and')
 * @method static Builder|Product whereLocations($value)
 * @method static Builder|Product whereMinimumStock($value)
 * @method static Builder|Product whereModifiedOn($value)
 * @method static Builder|Product whereName($value)
 * @method static Builder|Product whereNotifyMinimumStock($value)
 * @method static Builder|Product wherePriceReduced($value)
 * @method static Builder|Product whereProId($value)
 * @method static Builder|Product whereProductId($value)
 * @method static Builder|Product wherePurchasePrice($value)
 * @method static Builder|Product whereSalePrice($value)
 * @method static Builder|Product whereSellWithInventory($value)
 * @method static Builder|Product whereSemAmarilloDesde($value)
 * @method static Builder|Product whereSemAmarilloHasta($value)
 * @method static Builder|Product whereSemRedHasta($value)
 * @method static Builder|Product whereSemVerdeDesde($value)
 * @method static Builder|Product whereSemVerdeHasta($value)
 * @method static Builder|Product whereSimgURL($value)
 * @method static Builder|Product whereSku($value)
 * @method static Builder|Product whereStatus($value)
 * @method static Builder|Product whereStock($value)
 * @method static Builder|Product whereTags($value)
 * @method static Builder|Product whereUpdatedAt($value)
 * @method static Builder|Product whereWarehouseUserId($value)
 * @method static Builder|Product withPhoto()
 * @method static Builder|Product withStarOrder(int $customerId, array $statusIds)
 * @method static Builder|Product withTrashed()
 * @method static Builder|Product withoutCategories()
 * @method static Builder|Product withoutPhoto()
 * @method static Builder|Product withoutTrashed()
 * @property-read mixed $minimum_quantity
 * @property-read mixed $product_image
 * @property-read Collection<int, \App\Models\Image> $images
 * @property-read int|null $images_count
 * @property string|null $default_image_id
 * @property int|null $id_kor
 * @property-read \App\Models\Unit|null $unit
 * @method static Builder<static>|Product whereDefaultImageId($value)
 * @method static Builder<static>|Product whereUnitId($value)
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Product',
    description: 'Product model'
)]
class Product extends Model
{

    use HasFactory;
    use Filterable;

    use SoftDeletes;
    use HasFactory;

    const CREATED_AT = 'created_on';

    const UPDATED_AT = 'modified_on';
    protected $table = 'products';
    protected $primaryKey = 'pro_id';

    protected $casts = [
        'product_id' => 'int',
        'language_id' => 'int',
        'company_id' => 'int',
        'sale_price' => 'float',
        'stock' => 'float',
        'minimum_stock' => 'float',
        'notify_minimum_stock' => 'int',
        'fob_price' => 'float',
        'purchase_price' => 'float',
        'created_on' => 'datetime',
        'modified_on' => 'datetime',
        'status' => 'int',
        'sell_with_inventory' => 'int',
        'image_updated' => 'int',
        'price_reduced' => 'float',
        'sem_red_hasta' => 'int',
        'sem_amarillo_desde' => 'int',
        'sem_amarillo_hasta' => 'int',
        'sem_verde_desde' => 'int',
        'sem_verde_hasta' => 'int',
        'unit_id' => 'int',
        'category_group_id' => 'int',
        'category_subgroup_id' => 'int',
        'specifications' => 'array',
    ];

    protected $fillable = [
        'product_id',
        'language_id',
        'company_id',
        'sku',
        'name',
        'sale_price',
        'category_id',
        'barcode',
        'barcode_images',
        'tags',
        'descriptions',
        'specifications',
        'stock',
        'minimum_stock',
        'notify_minimum_stock',
        'currency_type',
        'fob_price',
        'purchase_price',
        'locations',
        'created_by',
        'created_on',
        'modified_on',
        'status',
        'Img',
        'imgURL',
        'simgURL',
        'cat_id',
        'cats_id',
        'sell_with_inventory',
        'image_updated',
        'price_reduced',
        'sem_red_hasta',
        'sem_amarillo_desde',
        'sem_amarillo_hasta',
        'sem_verde_desde',
        'sem_verde_hasta',
        'unit_id',
        'category_group_id',
        'category_subgroup_id',
        'default_image_id',
    ];

    protected $with = ['unit'];

    protected $appends = ['product_image', 'minimum_quantity', 'rating'];

    /**
     * Mutator para convertir sku a mayúsculas al guardar
     */
    public function setSkuAttribute($value)
    {
        $this->attributes['sku'] = $value !== null ? mb_strtoupper($value, 'UTF-8') : null;
    }

    /**
     * Mutator para convertir name a mayúsculas al guardar
     */
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = $value !== null ? mb_strtoupper($value, 'UTF-8') : null;
    }

    /**
     * Accessor para convertir sku a mayúsculas al leer
     */
    public function getSkuAttribute($value)
    {
        return $value !== null ? mb_strtoupper($value, 'UTF-8') : null;
    }

    /**
     * Accessor para convertir name a mayúsculas al leer
     */
    public function getNameAttribute($value)
    {
        return $value !== null ? mb_strtoupper($value, 'UTF-8') : null;
    }

    public function catalogs(): BelongsToMany
    {
        return $this->belongsToMany(Catalog::class, 'catalog_product', 'product_id', 'catalog_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'company_id');
    }

    public function language()
    {
        return $this->belongsTo(Language::class);
    }

    public function warehouse_user()
    {
        return $this->belongsTo(WarehouseUser::class);
    }

    public function catalog_carts()
    {
        return $this->hasMany(CatalogCart::class);
    }

    public function delivered_orders()
    {
        return $this->hasMany(DeliveredOrder::class);
    }

    public function detail_cars()
    {
        return $this->hasMany(DetailCar::class);
    }

    public function inventories()
    {
        return $this->hasMany(Inventory::class);
    }

    public function order_details()
    {
        return $this->hasMany(OrderDetail::class, 'product_id', 'pro_id');
    }

    public function images()
    {
        return $this->hasManyThrough(
            Image::class,
            ProductImage::class,
            'product_id',
            'uuid',
            'pro_id',
            'image_id'
        );
    }

    /**
     * Filas de product_images con orden (para listados sin N+1; usar con ->with('product_images.image')).
     */
    public function product_images(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProductImage::class, 'product_id', 'pro_id')
            ->orderByRaw('COALESCE(img_order, 999999) ASC')
            ->orderBy('img_id');
    }

    /**
     * Imagen por defecto en MinIO: products.default_image_id → images.uuid.
     */
    public function default_image()
    {
        return $this->belongsTo(Image::class, 'default_image_id', 'uuid');
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_product', 'product_id', 'category_id');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_type', 'currency_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id', 'unit_id');
    }

    public function productRating()
    {
        return $this->hasOne(ProductRating::class, 'product_id', 'pro_id');
    }

    /**
     * Rating 1–5 (desde product_ratings o estándar).
     */
    public function getRatingAttribute(): float
    {
        if ($this->relationLoaded('productRating') && $this->productRating) {
            return round((float) $this->productRating->rating, 1);
        }

        if (array_key_exists('rating', $this->attributes) && is_numeric($this->attributes['rating'])) {
            return round((float) $this->attributes['rating'], 1);
        }

        return \App\Services\ProductRatingService::DEFAULT_RATING;
    }

    public function categoryGroup()
    {
        return $this->belongsTo(CategoryGroup::class, 'category_group_id', 'group_id');
    }

    public function categorySubgroup()
    {
        return $this->belongsTo(CategorySubgroup::class, 'category_subgroup_id', 'subgroup_id');
    }

    public function categoryGroups()
    {
        return $this->belongsToMany(
            CategoryGroup::class,
            'product_category_group',
            'product_id',
            'group_id'
        );
    }

    public function categorySubgroups()
    {
        return $this->belongsToMany(
            CategorySubgroup::class,
            'product_category_subgroup',
            'product_id',
            'subgroup_id'
        );
    }

    /**
     * @param CustomerGroup $customerGroup
     * @param $reversePrice
     * @return float
     */
    public function getPriceForGroup($customerGroup, $reversePrice = false):float
    {
        $coefficient = 1;

        if (isset($customerGroup) && $customerGroup->percent_price_amount != 0) {
            $percentage = $customerGroup->percent_price_amount / 100;
            if ($customerGroup->isDecrease()) {
                $coefficient = 1 - $percentage;
            } elseif ($customerGroup->isIncrease()) {
                $coefficient = 1 + $percentage;
            }
        }

        if ($reversePrice && $coefficient != 0) {
            $coefficient = 1 / $coefficient;
        }

        return round($this->sale_price * $coefficient, 2);
    }

    public function getPriceForCustomer(Customer $customer, bool $reversePrice = false): float
    {
        return $this->getPriceForGroup($customer->customer_group, $reversePrice);
    }

    public function scopeWithStarOrder(Builder $query, int $customerId, array $statusIds): Builder
    {
        return $query->withExists(
            [
                'order_details as has_star_order' => function (Builder $subQuery) use (
                    $customerId,
                    $statusIds
                ) {
                    $subQuery->whereHas(
                        'order',
                        function (Builder $orderQuery) use ($customerId, $statusIds) {
                            $orderQuery->where('customer_id', $customerId)
                                ->whereIn('status_id', $statusIds);
                        }
                    );
                }
            ]
        );
    }


    public function scopeWithoutCategories($query)
    {
        return $query->whereDoesntHave('categories');
    }

    public function scopeWithoutPhoto($query)
    {
        return $query->whereNot(function ($q) {
            $q->withPhoto();
        });
    }

    public function scopeWithPhoto($query)
    {
        return $query->where(function ($q) {
            $q->whereHas('default_image', fn ($iq) => $iq->onActiveStorage())
                ->orWhereHas('product_images.image', fn ($iq) => $iq->onActiveStorage());
        });
    }

    public function validMinimumQuantity($quantity): bool
    {
        return $quantity >= $this->minimum_stock;
    }

    public function hasStock($quantity): bool
    {
        return $this->stock >= $quantity;

    }

    public function getMinimumQuantityAttribute()
    {
        if (!array_key_exists('minimum_stock', $this->attributes)) {
            $this->refresh();
        }
        return $this->minimum_stock;
    }

    public function getProductImageAttribute()
    {
        if ($this->relationLoaded('product_images') && $this->product_images->isNotEmpty()) {
            $first = $this->product_images->first();
            if ($first->relationLoaded('image') && $first->image) {
                return $first->image->uuid;
            }

            return $first->image_id;
        }

        $pi = ProductImage::query()
            ->where('product_id', $this->pro_id)
            ->orderByRaw('COALESCE(img_order, 999999) ASC')
            ->orderBy('img_id')
            ->with('image')
            ->first();

        if (! $pi) {
            return null;
        }

        return $pi->image?->uuid ?? $pi->image_id;
    }

    public function applyCustomerPricing(Customer $customer): self
    {
        if ($this->price_reduced > 0 && $customer) {
            $this->sale_price = $this->getPriceForCustomer($customer);
        }

        return $this;
    }

    public function inOpenedOrders(): bool
    {
        return $this->order_details()->whereHas('order', function($query) {
           $query->whereIn('status_id', [
               OrderStatusCodes::SHOPPING,
               OrderStatusCodes::READ,
               OrderStatusCodes::APPROVED,
               OrderStatusCodes::IN_PROCESS,
               OrderStatusCodes::PENDING,
               OrderStatusCodes::WAREHOUSE_ASSIGNED,
               OrderStatusCodes::WAREHOUSE_RETURNED,
               OrderStatusCodes::RETURN_REQUEST,
           ]);
        })->count() > 0;
    }
}
