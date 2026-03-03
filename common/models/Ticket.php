<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;

class Ticket extends ActiveRecord
{
    const STATUS_PENDING = 'pending';
    const STATUS_OPEN = 'open';
    const STATUS_CLOSED = 'closed';

    public static function tableName()
    {
        return 'tickets';
    }

    public function rules()
    {
        return [
            [['account_id', 'contact_jid'], 'required'],
            [['account_id', 'queue_id', 'assigned_agent_id'], 'integer'],
            [['contact_jid', 'owner_jid'], 'string', 'max' => 255],
            [['status'], 'in', 'range' => [self::STATUS_PENDING, self::STATUS_OPEN, self::STATUS_CLOSED]],
            [['created_at', 'updated_at'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'account_id' => 'Conta WhatsApp',
            'contact_jid' => 'JID do Contato',
            'status' => 'Status',
            'queue_id' => 'Fila',
            'assigned_agent_id' => 'Agente Atribuído',
            'created_at' => 'Criado em',
            'updated_at' => 'Atualizado em',
            'owner_jid' => 'JID do Proprietário',
        ];
    }

    // Relations

    public function getWhatsappAccount()
    {
        return $this->hasOne(WhatsappAccount::class, ['id' => 'account_id']);
    }

    public function getQueue()
    {
        return $this->hasOne(Queue::class, ['id' => 'queue_id']);
    }
}
