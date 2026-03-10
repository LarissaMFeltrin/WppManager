<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsappAccount extends Model
{
    protected $fillable = [
        'empresa_id',
        'user_id',
        'phone_number',
        'session_name',
        'owner_jid',
        'is_connected',
        'is_active',
        'service_port',
        'last_connection',
        'last_full_sync',
    ];

    protected $casts = [
        'is_connected' => 'boolean',
        'is_active' => 'boolean',
        'last_connection' => 'datetime',
        'last_full_sync' => 'datetime',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function atendentes(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_account', 'account_id', 'user_id')
            ->withTimestamps();
    }

    public function chats(): HasMany
    {
        return $this->hasMany(Chat::class, 'account_id');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class, 'account_id');
    }

    public function conversas(): HasMany
    {
        return $this->hasMany(Conversa::class, 'account_id');
    }
}
