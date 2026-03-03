<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;

class Message extends ActiveRecord
{
    const TYPE_TEXT = 'text';
    const TYPE_IMAGE = 'image';
    const TYPE_VIDEO = 'video';
    const TYPE_AUDIO = 'audio';
    const TYPE_DOCUMENT = 'document';
    const TYPE_STICKER = 'sticker';
    const TYPE_LOCATION = 'location';
    const TYPE_CONTACT = 'contact';

    const STATUS_PENDING = 'pending';
    const STATUS_SENT = 'sent';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_READ = 'read';
    const STATUS_FAILED = 'failed';

    public static function tableName()
    {
        return 'messages';
    }

    public function rules()
    {
        return [
            [['chat_id', 'message_key'], 'required'],
            [['chat_id'], 'integer'],
            [['message_key', 'from_jid', 'to_jid', 'link_preview_title', 'link_preview_url', 'owner_jid', 'quoted_message_id'], 'string', 'max' => 255],
            [['message_text', 'message_raw', 'link_preview_thumbnail'], 'string'],
            [['message_type'], 'in', 'range' => [
                self::TYPE_TEXT, self::TYPE_IMAGE, self::TYPE_VIDEO, self::TYPE_AUDIO,
                self::TYPE_DOCUMENT, self::TYPE_STICKER, self::TYPE_LOCATION, self::TYPE_CONTACT,
            ]],
            [['media_url'], 'string', 'max' => 500],
            [['media_mime_type'], 'string', 'max' => 100],
            [['is_from_me', 'is_edited'], 'boolean'],
            [['reactions'], 'string'],
            [['timestamp'], 'integer'],
            [['status'], 'in', 'range' => [
                self::STATUS_PENDING, self::STATUS_SENT, self::STATUS_DELIVERED,
                self::STATUS_READ, self::STATUS_FAILED,
            ]],
            [['latitude', 'longitude'], 'double'],
            [['link_preview_description', 'remote_media_url', 'quoted_text'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'chat_id' => 'Chat',
            'message_key' => 'Chave da Mensagem',
            'from_jid' => 'De (JID)',
            'to_jid' => 'Para (JID)',
            'message_text' => 'Texto da Mensagem',
            'message_type' => 'Tipo da Mensagem',
            'media_url' => 'URL da Mídia',
            'media_mime_type' => 'Tipo MIME da Mídia',
            'is_from_me' => 'Enviada por Mim',
            'timestamp' => 'Timestamp',
            'status' => 'Status',
            'created_at' => 'Criado em',
            'updated_at' => 'Atualizado em',
            'latitude' => 'Latitude',
            'longitude' => 'Longitude',
            'link_preview_title' => 'Título do Link',
            'link_preview_description' => 'Descrição do Link',
            'link_preview_url' => 'URL do Link',
            'remote_media_url' => 'URL Remota da Mídia',
            'owner_jid' => 'JID do Proprietário',
            'quoted_message_id' => 'ID da Mensagem Citada',
            'quoted_text' => 'Texto Citado',
            'is_edited' => 'Editada',
            'reactions' => 'Reações',
            'message_raw' => 'Mensagem Bruta',
            'link_preview_thumbnail' => 'Miniatura do Link',
        ];
    }

    // Relations

    public function getChat()
    {
        return $this->hasOne(Chat::class, ['id' => 'chat_id']);
    }
}
