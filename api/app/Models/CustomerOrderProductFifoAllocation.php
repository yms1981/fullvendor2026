<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerOrderProductFifoAllocation extends Model
{
    protected $table = 'customer_order_product_fifo_allocations';

    protected $fillable = [
        'fifo_id',
        'company_id',
        'customer_id',
        'product_id',
        'credit_order_id',
        'credit_detail_id',
        'credit_order_number',
        'qty',
        'credit_price',
    ];

    protected $casts = [
        'fifo_id' => 'int',
        'company_id' => 'int',
        'customer_id' => 'int',
        'product_id' => 'int',
        'credit_order_id' => 'int',
        'credit_detail_id' => 'int',
        'qty' => 'float',
        'credit_price' => 'float',
    ];

    public function fifo(): BelongsTo
    {
        return $this->belongsTo(CustomerOrderProductFifo::class, 'fifo_id');
    }

    public function creditOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'credit_order_id', 'order_id');
    }
}
