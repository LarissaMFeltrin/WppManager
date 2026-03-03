<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;

class DebugEvent extends ActiveRecord
{
    public static function tableName()
    {
        return 'debug_events';
    }

    public function rules()
    {
        return [
            [['event_type'], 'required'],
            [['event_type'], 'string', 'max' => 50],
            [['payload'], 'string'],
            [['created_at'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'event_type' => 'Tipo do Evento',
            'payload' => 'Payload',
            'created_at' => 'Criado em',
        ];
    }
}
