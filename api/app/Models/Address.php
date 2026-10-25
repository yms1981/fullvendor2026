<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 
 *
 * @property int $id
 * @property string $street
 * @property string $street_number
 * @property int|null $country_id
 * @property int|null $state_id
 * @property int|null $city_id
 * @property string|null $zone
 * @property string $zipcode
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\City|null $city
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Company> $companies
 * @property-read int|null $companies_count
 * @property-read \App\Models\Country|null $country
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Customer> $customers
 * @property-read int|null $customers_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Order> $orders
 * @property-read int|null $orders_count
 * @property-read \App\Models\State|null $state
 * @method static \Database\Factories\AddressFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address whereCityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address whereCountryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address whereStateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address whereStreet($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address whereStreetNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address whereZipcode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address whereZone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Address withoutTrashed()
 * @mixin \Eloquent
 */
class Address extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'street',
        'street_number',
        'country_id',
        'state_id',
        'state',
        'state_label',
        'city_id',
        'city',
        'city_label',
        'zone',
        'zipcode',
        'notes',
    ];

    protected $appends = [
        'state_text',
        'city_text',
    ];

    /**
     * Nombre de estado para UI/API: relación states o texto libre (columna state / state_label).
     */
    public function getStateTextAttribute(): ?string
    {
        if ($this->state_id) {
            $related = $this->getRelationValue('state');
            if ($related instanceof State) {
                return $related->name;
            }
            $loaded = $this->state()->first();

            return $loaded?->name;
        }

        foreach (['state', 'state_label'] as $column) {
            if (! array_key_exists($column, $this->attributes)) {
                continue;
            }
            $value = $this->attributes[$column];
            if ($value !== null && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        return null;
    }

    /**
     * Nombre de ciudad para UI/API: relación cities o texto libre (columna city / city_label).
     */
    public function getCityTextAttribute(): ?string
    {
        if ($this->city_id) {
            $related = $this->getRelationValue('city');
            if ($related instanceof City) {
                return $related->name;
            }
            $loaded = $this->city()->first();

            return $loaded?->name;
        }

        foreach (['city', 'city_label'] as $column) {
            if (! array_key_exists($column, $this->attributes)) {
                continue;
            }
            $value = $this->attributes[$column];
            if ($value !== null && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        return null;
    }

    public function companies()
    {
        return $this->belongsToMany(Company::class, 'address_company', 'address_id', 'company_id')
            ->withPivot('is_primary');
    }

    public function customers()
    {
        return $this->belongsToMany(Customer::class, 'address_customer', 'address_id', 'customer_id')
            ->withPivot('is_primary','type');
    }

    public function orders(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function country(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Country::class);
    }

    public function state(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\State::class);
    }

    public function city(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\City::class);
    }

    public function fullAddress(): string
    {
        return $this->street . ($this->street_number ? ' ' . $this->street_number : '');
    }
}
