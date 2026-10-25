<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $conversation_id
 * @property int $user_id
 * @property string $message_type text|image|audio
 * @property string|null $body
 */
class CompanyConversationMessage extends Model
{
    use SoftDeletes;

    protected $table = 'company_conversation_messages';

    protected $casts = [
        'conversation_id' => 'int',
        'user_id' => 'int',
    ];

    protected $fillable = [
        'conversation_id',
        'user_id',
        'message_type',
        'body',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(CompanyConversation::class, 'conversation_id', 'id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(CompanyConversationAttachment::class, 'message_id', 'id');
    }
}
