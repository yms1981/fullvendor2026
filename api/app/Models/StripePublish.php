<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $stripeID
 * @property string|null $secretAPI
 */
class StripePublish extends Model
{
    public $timestamps = false;

    protected $table = 'stripe_publish';

    protected $primaryKey = 'stripeID';

    protected $fillable = [
        'secretAPI',
    ];
}
