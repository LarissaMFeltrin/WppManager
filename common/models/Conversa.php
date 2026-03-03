<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;

class Conversa extends ActiveRecord
{
    const STATUS_AGUARDANDO = 'aguardando';
    const STATUS_EM_ATENDIMENTO = 'em_atendimento';
    const STATUS_FINALIZADA = 'finalizada';

    public static function tableName()
    {
        return 'conversas';
    }

    public function rules()
    {
        return [
            [['cliente_numero'], 'required'],
            [['cliente_numero'], 'string', 'max' => 20],
            [['cliente_nome'], 'string', 'max' => 100],
            [['chat_id', 'atendente_id', 'devolvida_por', 'account_id'], 'integer'],
            [['status'], 'in', 'range' => [self::STATUS_AGUARDANDO, self::STATUS_EM_ATENDIMENTO, self::STATUS_FINALIZADA]],
            [['bloqueada'], 'boolean'],
            [['notas'], 'string'],
            [['iniciada_em', 'atendida_em', 'finalizada_em', 'ultima_msg_em'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'cliente_numero' => 'Número do Cliente',
            'cliente_nome' => 'Nome do Cliente',
            'chat_id' => 'Chat',
            'atendente_id' => 'Atendente',
            'status' => 'Status',
            'bloqueada' => 'Bloqueada',
            'notas' => 'Notas',
            'iniciada_em' => 'Iniciada em',
            'atendida_em' => 'Atendida em',
            'finalizada_em' => 'Finalizada em',
            'ultima_msg_em' => 'Última Mensagem em',
        ];
    }

    // Relations

    public function getChat()
    {
        return $this->hasOne(Chat::class, ['id' => 'chat_id']);
    }

    public function getAtendente()
    {
        return $this->hasOne(Atendente::class, ['id' => 'atendente_id']);
    }

    public function getMensagens()
    {
        return $this->hasMany(Mensagem::class, ['conversa_id' => 'id']);
    }

    public function getWhatsappAccount()
    {
        return $this->hasOne(WhatsappAccount::class, ['id' => 'account_id']);
    }
}
