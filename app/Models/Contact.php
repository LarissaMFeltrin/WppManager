<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contact extends Model
{
    protected $fillable = [
        'account_id',
        'jid',
        'name',
        'phone_number',
        'profile_picture_url',
        'is_business',
        'is_blocked',
        'owner_jid',
    ];

    protected $casts = [
        'is_business' => 'boolean',
        'is_blocked' => 'boolean',
    ];

    /**
     * Accessor para phone - retorna phone_number ou extrai do JID
     */
    public function getPhoneAttribute(): ?string
    {
        if (!empty($this->phone_number)) {
            return $this->phone_number;
        }

        // Extrair do JID se phone_number estiver vazio
        if (!empty($this->jid)) {
            return preg_replace('/@.*$/', '', $this->jid);
        }

        return null;
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(WhatsappAccount::class, 'account_id');
    }
}
