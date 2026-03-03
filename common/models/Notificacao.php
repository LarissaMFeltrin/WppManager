<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;

class Notificacao extends ActiveRecord
{
    const TIPO_NOVA_CONVERSA = 'nova_conversa';
    const TIPO_NOVA_MENSAGEM = 'nova_mensagem';
    const TIPO_CONVERSA_FINALIZADA = 'conversa_finalizada';
    const TIPO_SISTEMA = 'sistema';

    public static function tableName()
    {
        return 'notificacoes';
    }

    public function rules()
    {
        return [
            [['atendente_id', 'tipo', 'titulo'], 'required'],
            [['atendente_id', 'conversa_id'], 'integer'],
            [['tipo'], 'in', 'range' => [
                self::TIPO_NOVA_CONVERSA, self::TIPO_NOVA_MENSAGEM,
                self::TIPO_CONVERSA_FINALIZADA, self::TIPO_SISTEMA,
            ]],
            [['titulo'], 'string', 'max' => 200],
            [['mensagem'], 'string'],
            [['lida'], 'boolean'],
            [['criada_em'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'atendente_id' => 'Atendente',
            'tipo' => 'Tipo',
            'conversa_id' => 'Conversa',
            'titulo' => 'Título',
            'mensagem' => 'Mensagem',
            'lida' => 'Lida',
            'criada_em' => 'Criada em',
        ];
    }

    // Relations

    public function getAtendente()
    {
        return $this->hasOne(Atendente::class, ['id' => 'atendente_id']);
    }

    public function getConversa()
    {
        return $this->hasOne(Conversa::class, ['id' => 'conversa_id']);
    }
}
