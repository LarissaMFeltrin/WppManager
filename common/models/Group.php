<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;

class Group extends ActiveRecord
{
    public static function tableName()
    {
        return 'groups';
    }

    public function rules()
    {
        return [
            [['account_id', 'group_id'], 'required'],
            [['account_id', 'participant_count'], 'integer'],
            [['group_id', 'group_name', 'owner_jid'], 'string', 'max' => 255],
            [['group_description'], 'string'],
            [['group_picture_url'], 'string', 'max' => 500],
            [['is_admin'], 'boolean'],
            [['created_at', 'updated_at'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'account_id' => 'Conta WhatsApp',
            'group_id' => 'ID do Grupo',
            'group_name' => 'Nome do Grupo',
            'group_description' => 'Descrição do Grupo',
            'group_picture_url' => 'Foto do Grupo',
            'owner_jid' => 'JID do Proprietário',
            'participant_count' => 'Quantidade de Participantes',
            'is_admin' => 'É Administrador',
            'created_at' => 'Criado em',
            'updated_at' => 'Atualizado em',
        ];
    }

    // Relations

    public function getWhatsappAccount()
    {
        return $this->hasOne(WhatsappAccount::class, ['id' => 'account_id']);
    }

    public function getGroupMembers()
    {
        return $this->hasMany(GroupMember::class, ['group_id' => 'id']);
    }
}
