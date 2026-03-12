<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactAlias extends Model
{
    protected $fillable = [
        'account_id',
        'primary_chat_id',
        'alias_jid',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(WhatsappAccount::class, 'account_id');
    }

    public function primaryChat(): BelongsTo
    {
        return $this->belongsTo(Chat::class, 'primary_chat_id');
    }
}
