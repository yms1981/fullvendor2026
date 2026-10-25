<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerOrderProductFifo extends Model
{
    public const STATUS_OPEN = 'open';

    public const STATUS_EXHAUSTED = 'exhausted';

    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'customer_order_product_fifo';

    protected $fillable = [
        'company_id',
        'customer_id',
        'product_id',
        'sku',
        'source_order_id',
        'source_detail_id',
        'source_order_number',
        'sale_price',
        'qty_sold',
        'qty_credited',
        'qty_remaining',
        'opened_at',
        'status',
    ];

    protected $casts = [
        'company_id' => 'int',
        'customer_id' => 'int',
        'product_id' => 'int',
        'source_order_id' => 'int',
        'source_detail_id' => 'int',
        'sale_price' => 'float',
        'qty_sold' => 'float',
        'qty_credited' => 'float',
        'qty_remaining' => 'float',
        'opened_at' => 'datetime',
    ];

    public function allocations(): HasMany
    {
        return $this->hasMany(CustomerOrderProductFifoAllocation::class, 'fifo_id');
    }

    public function sourceOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'source_order_id', 'order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'pro_id');
    }

    public function refreshStatus(): void
    {
        if ($this->status === self::STATUS_CANCELLED) {
            return;
        }
        $this->status = ((float) $this->qty_remaining) <= 0.00005
            ? self::STATUS_EXHAUSTED
            : self::STATUS_OPEN;
    }
}
