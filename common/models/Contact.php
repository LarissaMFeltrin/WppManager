<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;

class Contact extends ActiveRecord
{
    public static function tableName()
    {
        return 'contacts';
    }

    public function rules()
    {
        return [
            [['account_id', 'jid'], 'required'],
            [['account_id'], 'integer'],
            [['jid', 'name', 'owner_jid'], 'string', 'max' => 255],
            [['phone_number'], 'string', 'max' => 20],
            [['profile_picture_url'], 'string', 'max' => 500],
            [['is_blocked', 'is_business'], 'boolean'],
            [['created_at', 'updated_at'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'account_id' => 'Conta WhatsApp',
            'jid' => 'JID',
            'name' => 'Nome',
            'phone_number' => 'Número do Telefone',
            'profile_picture_url' => 'Foto de Perfil',
            'is_business' => 'Business',
            'is_blocked' => 'Bloqueado',
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
}
