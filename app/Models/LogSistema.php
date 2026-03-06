<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogSistema extends Model
{
    protected $table = 'logs_sistema';

    public $timestamps = false;

    protected $fillable = [
        'tipo',
        'nivel',
        'mensagem',
        'dados',
        'ip_origem',
        'user_agent',
        'criada_em',
    ];

    protected $casts = [
        'dados' => 'array',
        'criada_em' => 'datetime',
    ];

    public static function log(string $tipo, string $nivel, string $mensagem, ?array $dados = null): self
    {
        return self::create([
            'tipo' => $tipo,
            'nivel' => $nivel,
            'mensagem' => $mensagem,
            'dados' => $dados ? json_encode($dados) : null,
            'ip_origem' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'criada_em' => now(),
        ]);
    }
}
