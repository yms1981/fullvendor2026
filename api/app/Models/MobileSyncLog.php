<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MobileSyncLog extends Model
{
    protected $table = 'mobile_sync_logs';

    protected $fillable = [
        'mobile_device_id',
        'company_id',
        'sync_mode',
        'changes_count',
        'changes_products',
        'changes_customers',
        'changes_categories',
        'changes_orders',
        'since_at',
        'status',
        'synced_at',
        'completed_at',
    ];

    protected $casts = [
        'mobile_device_id' => 'int',
        'company_id' => 'int',
        'changes_count' => 'int',
        'changes_products' => 'int',
        'changes_customers' => 'int',
        'changes_categories' => 'int',
        'changes_orders' => 'int',
        'since_at' => 'datetime',
        'synced_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(MobileDevice::class, 'mobile_device_id');
    }
}
