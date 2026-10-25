<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string|null $secret
 */
class StripeSecret extends Model
{
    public $timestamps = false;

    protected $table = 'stripe_secret';

    protected $fillable = [
        'secret',
    ];
}
