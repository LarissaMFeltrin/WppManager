<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;

class Chat extends ActiveRecord
{
    const TYPE_INDIVIDUAL = 'individual';
    const TYPE_GROUP = 'group';
    const TYPE_BROADCAST = 'broadcast';

    public static function tableName()
    {
        return 'chats';
    }

    public function rules()
    {
        return [
            [['account_id', 'chat_id'], 'required'],
            [['account_id', 'unread_count'], 'integer'],
            [['last_message_timestamp'], 'integer'],
            [['chat_id', 'chat_name', 'owner_jid'], 'string', 'max' => 255],
            [['chat_type'], 'in', 'range' => [self::TYPE_INDIVIDUAL, self::TYPE_GROUP, self::TYPE_BROADCAST]],
            [['is_pinned', 'is_archived'], 'boolean'],
            [['created_at', 'updated_at'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'account_id' => 'Conta WhatsApp',
            'chat_id' => 'ID do Chat',
            'chat_name' => 'Nome do Chat',
            'chat_type' => 'Tipo do Chat',
            'is_pinned' => 'Fixado',
            'is_archived' => 'Arquivado',
            'unread_count' => 'Mensagens Não Lidas',
            'last_message_timestamp' => 'Última Mensagem',
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

    public function getMessages()
    {
        return $this->hasMany(Message::class, ['chat_id' => 'id']);
    }
}
