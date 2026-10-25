<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 
 *
 * @property int $id
 * @property int $order_id
 * @property int $user_id
 * @property string $type
 * @property string $note
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Order $order
 * @property-read \App\Models\User $user
 * @method static \Database\Factories\OrderNoteFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderNote newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderNote newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderNote onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderNote query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderNote whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderNote whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderNote whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderNote whereNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderNote whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderNote whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderNote whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderNote whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderNote withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderNote withoutTrashed()
 * @mixin \Eloquent
 */
class OrderNote extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'order_notes';

    protected $fillable = [
        'order_id',
        'user_id',
        'type',
        'note',
    ];
    public function order(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
