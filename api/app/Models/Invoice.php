<?php

namespace App\Models;

use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Factura local (tabla invoices). Campos Stripe alineados con legado Facturas.
 *
 * @property int $invoice_id
 * @property string|null $doc_number
 * @property int $subscription_id
 * @property int $company_id
 * @property \Illuminate\Support\Carbon|null $date
 * @property \Illuminate\Support\Carbon|null $date_expired
 * @property int|null $paid
 * @property int|null $days
 * @property int|null $entidad_id
 * @property string|null $concept
 * @property string|float|null $invoice_paid
 * @property string|float|null $debt
 * @property string|null $stripe_id
 * @property string|null $stripe_doc
 * @property string|null $stripe_url
 * @property string|null $stripe_pdf
 * @property string|null $stripe_status
 */
class Invoice extends Model
{
    use Filterable;

    public $timestamps = true;

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    protected $table = 'invoices';

    protected $primaryKey = 'invoice_id';

    public $incrementing = true;

    protected $fillable = [
        'doc_number',
        'subscription_id',
        'company_id',
        'date',
        'date_expired',
        'paid',
        'days',
        'entidad_id',
        'issuing_entity',
        'address_entity',
        'country_entity',
        'state_entity',
        'city_entity',
        'concept',
        'invoice_paid',
        'debt',
        'stripe_id',
        'stripe_doc',
        'stripe_url',
        'stripe_pdf',
        'stripe_status',
    ];

    protected function casts(): array
    {
        return [
            'invoice_id' => 'integer',
            'subscription_id' => 'integer',
            'company_id' => 'integer',
            'paid' => 'integer',
            'days' => 'integer',
            'entidad_id' => 'integer',
            'date' => 'datetime',
            'date_expired' => 'datetime',
            'invoice_paid' => 'decimal:2',
            'debt' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'company_id');
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class, 'subscription_id', 'subscription_id');
    }

    public function entidadEmisora(): BelongsTo
    {
        return $this->belongsTo(EntidadEmisora::class, 'entidad_id', 'entity_id');
    }
}
