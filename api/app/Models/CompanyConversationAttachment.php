<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $message_id
 * @property string $disk
 * @property string $path
 * @property string $mime_type
 * @property string|null $original_name
 * @property int $size_bytes
 * @property float|null $duration_seconds
 */
class CompanyConversationAttachment extends Model
{
    protected $table = 'company_conversation_attachments';

    protected $casts = [
        'message_id' => 'int',
        'size_bytes' => 'int',
        'duration_seconds' => 'float',
    ];

    protected $fillable = [
        'message_id',
        'disk',
        'path',
        'mime_type',
        'original_name',
        'size_bytes',
        'duration_seconds',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(CompanyConversationMessage::class, 'message_id', 'id');
    }

    public function storage()
    {
        return Storage::disk($this->disk ?: \App\Models\Image::defaultDisk());
    }
}
