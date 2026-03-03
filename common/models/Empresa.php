<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;

class Empresa extends ActiveRecord
{
    const STATUS_ATIVO = 1;
    const STATUS_INATIVO = 0;

    public static function tableName()
    {
        return 'empresas';
    }

    public function rules()
    {
        return [
            [['nome'], 'required'],
            [['nome'], 'string', 'max' => 200],
            [['cnpj'], 'string', 'max' => 20],
            [['telefone'], 'string', 'max' => 20],
            [['email'], 'string', 'max' => 100],
            [['email'], 'email'],
            [['logo'], 'string', 'max' => 500],
            [['status'], 'integer'],
            [['status'], 'in', 'range' => [self::STATUS_ATIVO, self::STATUS_INATIVO]],
            [['created_at', 'updated_at'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'nome' => 'Nome',
            'cnpj' => 'CNPJ',
            'telefone' => 'Telefone',
            'email' => 'E-mail',
            'logo' => 'Logo',
            'status' => 'Status',
            'created_at' => 'Criado em',
            'updated_at' => 'Atualizado em',
        ];
    }

    // Relations

    public function getWhatsappAccounts()
    {
        return $this->hasMany(WhatsappAccount::class, ['empresa_id' => 'id']);
    }

    public function getUsers()
    {
        return $this->hasMany(User::class, ['empresa_id' => 'id']);
    }

    public function getAtendentes()
    {
        return $this->hasMany(Atendente::class, ['empresa_id' => 'id']);
    }
}
