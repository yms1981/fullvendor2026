<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * 
 *
 * @method static \Database\Factories\StatusOrderFlowFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|StatusOrderFlow newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|StatusOrderFlow newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|StatusOrderFlow query()
 * @property int $id
 * @property int $status_order_id
 * @property int $order_flow_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|StatusOrderFlow whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StatusOrderFlow whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StatusOrderFlow whereOrderFlowId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StatusOrderFlow whereStatusOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StatusOrderFlow whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class StatusOrderFlow extends Pivot
{
    use HasFactory;

    protected $foreignKey = 'status_order_id';
    protected $relatedKey = 'order_flow_id';
    protected $casts = [
        'status_order_id' => 'int',
        'order_flow_id' => 'int'
    ];

    protected $fillable = [
        'status_order_id',
        'order_flow_id'
    ];
}
