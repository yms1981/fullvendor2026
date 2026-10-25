<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Rating agregado de un producto por empresa.
 *
 * @property int $id
 * @property int $company_id
 * @property int $product_id
 * @property float $rating
 * @property float $sales_count
 * @property float $sales_amount
 * @property string $source
 */
class ProductRating extends Model
{
    protected $table = 'product_ratings';

    protected $fillable = [
        'company_id',
        'product_id',
        'rating',
        'sales_count',
        'sales_amount',
        'source',
    ];

    protected $casts = [
        'company_id' => 'int',
        'product_id' => 'int',
        'rating' => 'float',
        'sales_count' => 'float',
        'sales_amount' => 'float',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'company_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'pro_id');
    }
}
