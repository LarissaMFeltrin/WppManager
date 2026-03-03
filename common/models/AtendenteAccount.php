<?php

namespace common\models;

use yii\db\ActiveRecord;

class AtendenteAccount extends ActiveRecord
{
    public static function tableName()
    {
        return 'atendente_account';
    }

    public function rules()
    {
        return [
            [['atendente_id', 'account_id'], 'required'],
            [['atendente_id', 'account_id'], 'integer'],
            [['atendente_id', 'account_id'], 'unique', 'targetAttribute' => ['atendente_id', 'account_id']],
            [['created_at'], 'safe'],
        ];
    }

    public function getAtendente()
    {
        return $this->hasOne(Atendente::class, ['id' => 'atendente_id']);
    }

    public function getWhatsappAccount()
    {
        return $this->hasOne(WhatsappAccount::class, ['id' => 'account_id']);
    }
}
