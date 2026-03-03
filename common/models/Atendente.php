<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;

class Atendente extends ActiveRecord
{
    const STATUS_ONLINE = 'online';
    const STATUS_OFFLINE = 'offline';
    const STATUS_OCUPADO = 'ocupado';

    public static function tableName()
    {
        return 'atendentes';
    }

    public function rules()
    {
        return [
            [['empresa_id', 'nome', 'email', 'senha'], 'required'],
            [['empresa_id', 'user_id', 'max_conversas', 'conversas_ativas'], 'integer'],
            [['max_conversas'], 'default', 'value' => 5],
            [['conversas_ativas'], 'default', 'value' => 0],
            [['nome', 'email'], 'string', 'max' => 100],
            [['email'], 'email'],
            [['senha'], 'string', 'max' => 255],
            [['status'], 'in', 'range' => [self::STATUS_ONLINE, self::STATUS_OFFLINE, self::STATUS_OCUPADO]],
            [['ultimo_acesso', 'created_at', 'updated_at'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'empresa_id' => 'Empresa',
            'user_id' => 'Usuário',
            'nome' => 'Nome',
            'email' => 'E-mail',
            'senha' => 'Senha',
            'status' => 'Status',
            'max_conversas' => 'Máximo de Conversas',
            'conversas_ativas' => 'Conversas Ativas',
            'ultimo_acesso' => 'Último Acesso',
            'created_at' => 'Criado em',
            'updated_at' => 'Atualizado em',
        ];
    }

    /**
     * Auto-vincular user_id pelo email ao salvar.
     */
    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        // Se user_id nao foi definido, tentar encontrar pelo email
        if (!$this->user_id && $this->email) {
            $user = User::findOne(['email' => $this->email]);
            if ($user) {
                $this->user_id = $user->id;
            }
        }

        return true;
    }

    // Relations

    public function getEmpresa()
    {
        return $this->hasOne(Empresa::class, ['id' => 'empresa_id']);
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function getConversas()
    {
        return $this->hasMany(Conversa::class, ['atendente_id' => 'id']);
    }

    public function getAtendenteAccounts()
    {
        return $this->hasMany(AtendenteAccount::class, ['atendente_id' => 'id']);
    }

    public function getWhatsappAccounts()
    {
        return $this->hasMany(WhatsappAccount::class, ['id' => 'account_id'])
            ->viaTable('atendente_account', ['atendente_id' => 'id']);
    }

    /**
     * Retorna array de account_ids vinculados a este atendente.
     */
    public function getAccountIds()
    {
        return AtendenteAccount::find()
            ->select('account_id')
            ->where(['atendente_id' => $this->id])
            ->column();
    }
}
