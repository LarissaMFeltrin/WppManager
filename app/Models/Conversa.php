<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Conversa extends Model
{
    protected $fillable = [
        'cliente_numero',
        'cliente_nome',
        'chat_id',
        'account_id',
        'atendente_id',
        'devolvida_por',
        'status',
        'bloqueada',
        'notas',
        'iniciada_em',
        'atendida_em',
        'finalizada_em',
        'ultima_msg_em',
        'cliente_aguardando_desde',
    ];

    protected $casts = [
        'bloqueada' => 'boolean',
        'iniciada_em' => 'datetime',
        'atendida_em' => 'datetime',
        'finalizada_em' => 'datetime',
        'ultima_msg_em' => 'datetime',
        'cliente_aguardando_desde' => 'datetime',
    ];

    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(WhatsappAccount::class, 'account_id');
    }

    public function atendente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atendente_id');
    }

    public function devolvidaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'devolvida_por');
    }

    public function scopeAguardando($query)
    {
        return $query->where('status', 'aguardando');
    }

    public function scopeEmAtendimento($query)
    {
        return $query->where('status', 'em_atendimento');
    }

    public function scopeFinalizada($query)
    {
        return $query->where('status', 'finalizada');
    }
}
