<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 
 *
 * @property int $id
 * @property int $address_id
 * @property int $company_id
 * @property bool $is_primary
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AddressCompany newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AddressCompany newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AddressCompany onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AddressCompany query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AddressCompany whereAddressId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AddressCompany whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AddressCompany whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AddressCompany whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AddressCompany whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AddressCompany whereIsPrimary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AddressCompany whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AddressCompany withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AddressCompany withoutTrashed()
 * @mixin \Eloquent
 */
class AddressCompany extends Pivot
{
    use SoftDeletes;

    protected $casts = [
        'address_id' => 'int',
        'company_id' => 'int',
        'is_primary' => 'bool'
    ];

    protected $fillable = [
        'address_id',
        'company_id',
        'is_primary'
    ];
}
