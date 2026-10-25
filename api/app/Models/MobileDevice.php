<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MobileDevice extends Model
{
    use SoftDeletes;

    protected $table = 'mobile_devices';

    protected $fillable = [
        'device_uuid',
        'user_id',
        'company_id',
        'device_name',
        'device_type',
        'os_version',
        'app_version',
        'model',
        'manufacturer',
        'push_token',
        'last_used_at',
        'last_sync_at',
    ];

    protected $casts = [
        'user_id' => 'int',
        'company_id' => 'int',
        'last_used_at' => 'datetime',
        'last_sync_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'company_id');
    }

    public function syncLogs(): HasMany
    {
        return $this->hasMany(MobileSyncLog::class, 'mobile_device_id');
    }
}
