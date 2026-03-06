<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable = [
        'chat_id',
        'message_key',
        'from_jid',
        'to_jid',
        'message_text',
        'message_type',
        'media_url',
        'media_mime_type',
        'is_from_me',
        'sent_by_user_id',
        'timestamp',
        'status',
        'is_edited',
        'is_deleted',
        'reactions',
        'quoted_message_id',
        'quoted_text',
        'latitude',
        'longitude',
        'link_preview_title',
        'link_preview_description',
        'link_preview_url',
        'link_preview_thumbnail',
        'remote_media_url',
        'message_raw',
    ];

    protected $casts = [
        'is_from_me' => 'boolean',
        'is_edited' => 'boolean',
        'is_deleted' => 'boolean',
        'timestamp' => 'integer',
        'reactions' => 'array',
        'message_raw' => 'array',
    ];

    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }

    public function sentByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by_user_id');
    }

    public function quotedMessage(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'quoted_message_id', 'message_key');
    }
}
