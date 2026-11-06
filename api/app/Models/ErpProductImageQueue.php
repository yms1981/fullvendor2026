<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ErpProductImageQueue extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_DOWNLOADED = 'downloaded';

    public const STATUS_FAILED = 'failed';

    public const STATUS_SKIPPED = 'skipped';

    protected $table = 'erp_product_image_queue';

    protected $fillable = [
        'company_id',
        'product_id',
        'sku',
        'image_url',
        'url_hash',
        'sort_order',
        'is_default',
        'status',
        'attempts',
        'last_error',
        'queued_at',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'company_id' => 'int',
        'product_id' => 'int',
        'sort_order' => 'int',
        'is_default' => 'bool',
        'attempts' => 'int',
        'queued_at' => 'datetime',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'company_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'pro_id');
    }
}
