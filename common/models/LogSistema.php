<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;

class LogSistema extends ActiveRecord
{
    const TIPO_WEBHOOK = 'webhook';
    const TIPO_API = 'api';
    const TIPO_ATENDIMENTO = 'atendimento';
    const TIPO_ERRO = 'erro';
    const TIPO_INFO = 'info';

    const NIVEL_DEBUG = 'debug';
    const NIVEL_INFO = 'info';
    const NIVEL_WARNING = 'warning';
    const NIVEL_ERROR = 'error';
    const NIVEL_CRITICAL = 'critical';

    public static function tableName()
    {
        return 'logs_sistema';
    }

    public function rules()
    {
        return [
            [['tipo', 'nivel', 'mensagem'], 'required'],
            [['tipo'], 'in', 'range' => [
                self::TIPO_WEBHOOK, self::TIPO_API, self::TIPO_ATENDIMENTO,
                self::TIPO_ERRO, self::TIPO_INFO,
            ]],
            [['nivel'], 'in', 'range' => [
                self::NIVEL_DEBUG, self::NIVEL_INFO, self::NIVEL_WARNING,
                self::NIVEL_ERROR, self::NIVEL_CRITICAL,
            ]],
            [['mensagem', 'dados', 'user_agent'], 'string'],
            [['ip_origem'], 'string', 'max' => 45],
            [['criada_em'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'tipo' => 'Tipo',
            'nivel' => 'Nível',
            'mensagem' => 'Mensagem',
            'dados' => 'Dados',
            'ip_origem' => 'IP de Origem',
            'user_agent' => 'User Agent',
            'criada_em' => 'Criada em',
        ];
    }

    /**
     * Grava um log no sistema.
     * @param string $tipo
     * @param string $nivel
     * @param string $mensagem
     * @param array|string|null $dados
     * @return bool
     */
    public static function gravar($tipo, $nivel, $mensagem, $dados = null)
    {
        try {
            $log = new self();
            $log->tipo = $tipo;
            $log->nivel = $nivel;
            $log->mensagem = $mensagem;
            $log->dados = is_array($dados) ? json_encode($dados, JSON_UNESCAPED_UNICODE) : $dados;
            $log->ip_origem = Yii::$app->request->getUserIP() ?? null;
            $log->user_agent = Yii::$app->request->getUserAgent() ?? null;
            return $log->save(false);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
