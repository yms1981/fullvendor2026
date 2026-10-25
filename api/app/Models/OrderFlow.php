<?php

namespace App\Models;

use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;


/**
 * 
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\StatusOrder> $statuses
 * @property-read int|null $statuses_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OrderFlowTransition> $transitions
 * @property-read int|null $transitions_count
 * @method static \Database\Factories\OrderFlowFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|OrderFlow newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderFlow newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderFlow query()
 * @property int $id
 * @property string $name
 * @property int|null $company_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|OrderFlow whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderFlow whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderFlow whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderFlow whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderFlow whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderFlow filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderFlow paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderFlow simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderFlow whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderFlow whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderFlow whereLike($column, $value, $boolean = 'and')
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Orderflow',
    description: 'OrderFlow model'
)]
class OrderFlow extends Model
{
    use HasFactory;
    use Filterable;

    protected $fillable = [
        'name',
        'company_id'
    ];

    public function statuses():HasManyThrough {
         return $this->hasManyThrough(StatusOrder::class,StatusOrderFlow::class,'status_order_id','id_status');
    }

    public function transitions():HasMany {
        return $this->hasMany(OrderFlowTransition::class,'order_flow_id');
    }
}
