<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;

class Queue extends ActiveRecord
{
    public static function tableName()
    {
        return 'queues';
    }

    public function rules()
    {
        return [
            [['name'], 'required'],
            [['name'], 'string', 'max' => 255],
            [['description'], 'string'],
            [['created_at'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Nome',
            'description' => 'Descrição',
            'created_at' => 'Criado em',
        ];
    }

    // Relations

    public function getTickets()
    {
        return $this->hasMany(Ticket::class, ['queue_id' => 'id']);
    }
}
