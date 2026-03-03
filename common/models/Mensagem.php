<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;

class Mensagem extends ActiveRecord
{
    const TIPO_TEXTO = 'texto';
    const TIPO_IMAGEM = 'imagem';
    const TIPO_AUDIO = 'audio';
    const TIPO_VIDEO = 'video';
    const TIPO_DOCUMENTO = 'documento';
    const TIPO_STICKER = 'sticker';
    const TIPO_LOCATION = 'location';

    public static function tableName()
    {
        return 'mensagens';
    }

    public function rules()
    {
        return [
            [['conversa_id', 'remetente', 'conteudo'], 'required'],
            [['conversa_id'], 'integer'],
            [['remetente', 'destinatario'], 'string', 'max' => 20],
            [['tipo'], 'in', 'range' => [
                self::TIPO_TEXTO, self::TIPO_IMAGEM, self::TIPO_AUDIO, self::TIPO_VIDEO,
                self::TIPO_DOCUMENTO, self::TIPO_STICKER, self::TIPO_LOCATION,
            ]],
            [['conteudo', 'midia_url'], 'string'],
            [['midia_mimetype'], 'string', 'max' => 100],
            [['enviada_por_atendente', 'lida'], 'boolean'],
            [['criada_em'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'conversa_id' => 'Conversa',
            'remetente' => 'Remetente',
            'destinatario' => 'Destinatário',
            'tipo' => 'Tipo',
            'conteudo' => 'Conteúdo',
            'midia_url' => 'URL da Mídia',
            'midia_mimetype' => 'Tipo MIME da Mídia',
            'enviada_por_atendente' => 'Enviada pelo Atendente',
            'lida' => 'Lida',
            'criada_em' => 'Criada em',
        ];
    }

    // Relations

    public function getConversa()
    {
        return $this->hasOne(Conversa::class, ['id' => 'conversa_id']);
    }
}
