<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Conversación de chat empresarial (Sellers, Warehouse, Managers).
 *
 * @property int $id
 * @property int $company_id
 * @property string|null $subject
 * @property string|null $context_type
 * @property int|null $context_id
 * @property int $created_by
 * @property \Illuminate\Support\Carbon|null $last_message_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class CompanyConversation extends Model
{
    use SoftDeletes;

    protected $table = 'company_conversations';

    protected $casts = [
        'company_id' => 'int',
        'context_id' => 'int',
        'created_by' => 'int',
        'last_message_at' => 'datetime',
    ];

    protected $fillable = [
        'company_id',
        'subject',
        'context_type',
        'context_id',
        'created_by',
        'last_message_at',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'company_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(CompanyConversationParticipant::class, 'conversation_id', 'id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(CompanyConversationMessage::class, 'conversation_id', 'id');
    }
}
