<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyScheduledTaskLog extends Model
{
    public const TRIGGER_MANUAL = 'manual';

    public const TRIGGER_CRON = 'cron';

    protected $table = 'company_scheduled_task_logs';

    protected $fillable = [
        'company_id',
        'scheduled_task_id',
        'task_type',
        'trigger',
        'status',
        'message',
        'summary',
        'started_at',
        'finished_at',
        'duration_ms',
    ];

    protected $casts = [
        'company_id' => 'int',
        'scheduled_task_id' => 'int',
        'summary' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'duration_ms' => 'int',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'company_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(CompanyScheduledTask::class, 'scheduled_task_id');
    }
}
