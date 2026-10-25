<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $doc_id
 * @property string|null $urlStripe
 * @property float|null $amount
 * @property int|null $pagado
 * @property float|null $montopagado
 * @property \Illuminate\Support\Carbon|null $fecha
 * @property \Illuminate\Support\Carbon|null $fechapagado
 */
class StripeDocument extends Model
{
    public $timestamps = false;

    protected $table = 'stripe_documents';

    protected $primaryKey = 'doc_id';

    public $incrementing = true;

    protected $fillable = [
        'urlStripe',
        'amount',
        'pagado',
        'montopagado',
        'fecha',
        'fechapagado',
    ];

    protected function casts(): array
    {
        return [
            'doc_id' => 'integer',
            'amount' => 'float',
            'pagado' => 'integer',
            'montopagado' => 'float',
            'fecha' => 'datetime',
            'fechapagado' => 'datetime',
        ];
    }
}
