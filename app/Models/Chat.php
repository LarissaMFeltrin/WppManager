<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Chat extends Model
{
    protected $fillable = [
        'account_id',
        'chat_id',
        'chat_name',
        'chat_type',
        'is_pinned',
        'is_archived',
        'unread_count',
        'last_message_timestamp',
        'owner_jid',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'is_archived' => 'boolean',
        'unread_count' => 'integer',
        'last_message_timestamp' => 'integer',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(WhatsappAccount::class, 'account_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function conversa(): HasOne
    {
        return $this->hasOne(Conversa::class);
    }

    public function lastMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany('timestamp');
    }
}
