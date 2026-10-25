<?php

namespace App\Models;

use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $plan_id
 * @property string|null $title
 * @property string|null $description
 * @property string|null $billing_period
 * @property int|null $status
 * @property float|string|null $price
 * @property string|null $stripeld
 * @property string|null $stripeName
 * @property string|null $stripe_doc
 * @property string|null $stripe_url
 * @property string|null $stripe_pdf
 * @property string|null $stripe_status
 * @property string|null $plan_name_spa
 * @property string|null $plan_name_eng
 * @property string|null $line1_spa
 * @property string|null $line1_eng
 * @property float|null $payment
 * @property int|null $days
 * @property int|null $max_Customers
 * @property string|null $paymenttextp
 * @property int|null $paymentperiocity
 * @property int|null $neverexpire
 * @property int|null $ecommerce_permission
 */
class Plan extends Model
{
    use Filterable;

    public $timestamps = true;

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    protected $table = 'plan';

    protected $primaryKey = 'plan_id';

    public $incrementing = true;

    protected $fillable = [
        'language1_id',
        'language2_id',
        'title',
        'description',
        'billing_period',
        'status',
        'max_clients',
        'max_orders',
        'max_users',
        'price',
        'stripeld',
        'stripeName',
        'stripe_doc',
        'stripe_url',
        'stripe_pdf',
        'stripe_status',
        'plan_name_spa',
        'plan_name_eng',
        'line1_spa',
        'line1_eng',
        'payment',
        'days',
        'max_Customers',
        'paymenttextp',
        'paymentperiocity',
        'neverexpire',
        'ecommerce_permission',
    ];

    protected function casts(): array
    {
        return [
            'plan_id' => 'integer',
            'language1_id' => 'integer',
            'language2_id' => 'integer',
            'status' => 'integer',
            'max_clients' => 'integer',
            'max_orders' => 'integer',
            'max_users' => 'integer',
            'price' => 'decimal:2',
            'payment' => 'float',
            'days' => 'integer',
            'max_Customers' => 'integer',
            'paymentperiocity' => 'integer',
            'neverexpire' => 'integer',
            // ecommerce_permission es BIT(1) en BD: PDO lo devuelve como string "\x00"/"\x01".
            // El cast a 'boolean' funciona porque Laravel evalúa truthiness sobre la cadena binaria.
            'ecommerce_permission' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
