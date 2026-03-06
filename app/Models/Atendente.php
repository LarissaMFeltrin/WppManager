<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Atendente extends Model
{
    protected $fillable = [
        'empresa_id',
        'user_id',
        'nome',
        'email',
        'senha',
        'status',
        'max_conversas',
        'conversas_ativas',
        'ultimo_acesso',
    ];

    protected $hidden = [
        'senha',
    ];

    protected $casts = [
        'ultimo_acesso' => 'datetime',
        'max_conversas' => 'integer',
        'conversas_ativas' => 'integer',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function whatsappAccounts(): BelongsToMany
    {
        return $this->belongsToMany(WhatsappAccount::class, 'atendente_account', 'atendente_id', 'account_id')
            ->withTimestamps();
    }

    public function conversas(): HasMany
    {
        return $this->hasMany(Conversa::class);
    }

    public function conversasDevolvidas(): HasMany
    {
        return $this->hasMany(Conversa::class, 'devolvida_por');
    }
}
