<?php

namespace App\Models;

use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $subscription_id
 * @property int $company_id
 * @property int $plan_id
 * @property int|null $status
 * @property string|null $start_period_date
 * @property string|null $end_period_date
 * @property string|null $notes
 * @property string|null $stripe_subscription_id
 * @property string|null $stripe_status
 * @property string|null $stripe_price_id
 * @property string|null $stripe_currency
 * @property string|null $stripe_collection_method
 * @property bool $stripe_cancel_at_period_end
 * @property string|null $stripe_current_period_start
 * @property string|null $stripe_current_period_end
 * @property string|null $stripe_trial_start
 * @property string|null $stripe_trial_end
 * @property string|null $stripe_cancel_at
 * @property string|null $stripe_canceled_at
 * @property string|null $stripe_ended_at
 * @property string|null $stripe_latest_invoice_id
 * @property string|null $stripe_default_payment_method_id
 * @property string|null $stripe_created_at
 */
class Subscription extends Model
{
    use Filterable;

    public $timestamps = true;

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    protected $table = 'subscriptions';

    protected $primaryKey = 'subscription_id';

    public $incrementing = true;

    protected $fillable = [
        'company_id',
        'plan_id',
        'status',
        'start_period_date',
        'end_period_date',
        'notes',
        'stripe_subscription_id',
        'stripe_status',
        'stripe_price_id',
        'stripe_currency',
        'stripe_collection_method',
        'stripe_cancel_at_period_end',
        'stripe_current_period_start',
        'stripe_current_period_end',
        'stripe_trial_start',
        'stripe_trial_end',
        'stripe_cancel_at',
        'stripe_canceled_at',
        'stripe_ended_at',
        'stripe_latest_invoice_id',
        'stripe_default_payment_method_id',
        'stripe_created_at',
    ];

    protected function casts(): array
    {
        return [
            'subscription_id' => 'integer',
            'company_id' => 'integer',
            'plan_id' => 'integer',
            'status' => 'integer',
            'start_period_date' => 'date',
            'end_period_date' => 'date',
            'stripe_cancel_at_period_end' => 'boolean',
            'stripe_current_period_start' => 'datetime',
            'stripe_current_period_end' => 'datetime',
            'stripe_trial_start' => 'datetime',
            'stripe_trial_end' => 'datetime',
            'stripe_cancel_at' => 'datetime',
            'stripe_canceled_at' => 'datetime',
            'stripe_ended_at' => 'datetime',
            'stripe_created_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'company_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id', 'plan_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'subscription_id', 'subscription_id');
    }
}
