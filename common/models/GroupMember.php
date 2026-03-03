<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;

class GroupMember extends ActiveRecord
{
    public static function tableName()
    {
        return 'group_members';
    }

    public function rules()
    {
        return [
            [['group_id', 'member_jid'], 'required'],
            [['group_id'], 'integer'],
            [['member_jid', 'member_name'], 'string', 'max' => 255],
            [['is_admin', 'is_super_admin'], 'boolean'],
            [['joined_at', 'created_at'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'group_id' => 'Grupo',
            'member_jid' => 'JID do Membro',
            'member_name' => 'Nome do Membro',
            'is_admin' => 'É Administrador',
            'is_super_admin' => 'É Super Administrador',
            'joined_at' => 'Entrou em',
            'created_at' => 'Criado em',
        ];
    }

    // Relations

    public function getGroup()
    {
        return $this->hasOne(Group::class, ['id' => 'group_id']);
    }
}
