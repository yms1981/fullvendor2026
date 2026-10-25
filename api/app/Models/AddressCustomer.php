<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 
 *
 * @property int $id
 * @property int $address_id
 * @property int $customer_id
 * @property bool $is_primary
 * @property string $type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AddressCustomer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AddressCustomer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AddressCustomer onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AddressCustomer query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AddressCustomer whereAddressId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AddressCustomer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AddressCustomer whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AddressCustomer whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AddressCustomer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AddressCustomer whereIsPrimary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AddressCustomer whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AddressCustomer whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AddressCustomer withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AddressCustomer withoutTrashed()
 * @mixin \Eloquent
 */
class AddressCustomer extends Pivot
{
    use SoftDeletes;

    protected $table = 'address_customer';

    protected $casts = [
        'address_id' => 'int',
        'customer_id' => 'int',
        'is_primary' => 'bool',
        'type' => 'string'
    ];

    protected $fillable = [
        'address_id',
        'customer_id',
        'is_primary',
        'type'
    ];
}
