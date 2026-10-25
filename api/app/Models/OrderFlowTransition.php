<?php

namespace App\Models;

use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


/**
 * 
 *
 * @property-read \App\Models\OrderFlow|null $orderFlow
 * @method static \Database\Factories\OrderFlowTransitionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|OrderFlowTransition newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderFlowTransition newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderFlowTransition query()
 * @property int $id
 * @property string $name
 * @property int $order_flow_id
 * @property int $origin_status_order_id
 * @property int $destination_status_order_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|OrderFlowTransition whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderFlowTransition whereDestinationStatusOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderFlowTransition whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderFlowTransition whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderFlowTransition whereOrderFlowId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderFlowTransition whereOriginStatusOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderFlowTransition whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderFlowTransition filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderFlowTransition paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderFlowTransition simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderFlowTransition whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderFlowTransition whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderFlowTransition whereLike($column, $value, $boolean = 'and')
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Orderflowtransition',
    description: 'OrderFlowTransition model'
)]
class OrderFlowTransition extends Model
{
    use HasFactory;
    use Filterable;

    protected $fillable = [
        'name',
        'order_flow_id',
        'origin_status_order_id',
        'destination_status_order_id'
    ];

    public function orderFlow():BelongsTo
    {
        return $this->belongsTo(OrderFlow::class);
    }
}
