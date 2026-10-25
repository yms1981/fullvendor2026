<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostalCode extends Model
{
    protected $table = 'postal_codes';

    protected $fillable = [
        'country_iso2',
        'postal_code',
        'place_name',
        'admin_name1',
        'admin_code1',
        'admin_name2',
        'admin_code2',
        'admin_name3',
        'admin_code3',
        'latitude',
        'longitude',
        'accuracy',
        'country_id',
        'state_id',
        'city_id',
    ];
}
