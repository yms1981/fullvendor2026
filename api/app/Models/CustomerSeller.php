<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class CustomerSeller
 *
 * @property int $customer_id
 * @property int seller_id
 * @package App\Models
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder|CustomerSeller newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CustomerSeller newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CustomerSeller onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|CustomerSeller query()
 * @method static \Illuminate\Database\Eloquent\Builder|CustomerSeller whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CustomerSeller whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CustomerSeller whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CustomerSeller whereSellerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CustomerSeller whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CustomerSeller withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|CustomerSeller withoutTrashed()
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Customerseller',
    description: 'CustomerSeller model'
)]
class CustomerSeller extends Model
{
    use SoftDeletes;

    protected $table = 'customer_seller';
    protected $casts = [
        'customer_id' => 'int',
        'seller_id' => 'int'
    ];
    protected $fillable = [
        'customer_id',
        'seller_id'
    ];

}
