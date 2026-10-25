<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Participante de una conversación empresarial.
 *
 * @property int $id
 * @property int $conversation_id
 * @property int $user_id
 * @property string $role_type seller|warehouse|manager|customer
 * @property \Illuminate\Support\Carbon|null $last_read_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class CompanyConversationParticipant extends Model
{
    use SoftDeletes;

    protected $table = 'company_conversation_participants';

    protected $casts = [
        'conversation_id' => 'int',
        'user_id' => 'int',
        'last_read_at' => 'datetime',
    ];

    protected $fillable = [
        'conversation_id',
        'user_id',
        'role_type',
        'last_read_at',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(CompanyConversation::class, 'conversation_id', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
