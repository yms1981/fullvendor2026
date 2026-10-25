<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Contadores diarios de mensajería (email / SMS / WhatsApp) por compañía.
 *
 * @property int $id
 * @property int $company_id
 * @property Carbon|string $stat_date
 * @property int $email_count
 * @property int $sms_count
 * @property int $whatsapp_count
 */
class CompanyMessagingDaily extends Model
{
    protected $table = 'company_messaging_daily';

    protected $fillable = [
        'company_id',
        'stat_date',
        'email_count',
        'sms_count',
        'whatsapp_count',
    ];

    protected $casts = [
        'company_id' => 'int',
        'stat_date' => 'date',
        'email_count' => 'int',
        'sms_count' => 'int',
        'whatsapp_count' => 'int',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'company_id');
    }
}
