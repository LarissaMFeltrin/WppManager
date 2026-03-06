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

    public function account(): BelongsTo
    {
        return $this->belongsTo(WhatsappAccount::class, 'account_id');
    }
}
