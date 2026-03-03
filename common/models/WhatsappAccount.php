<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;

class WhatsappAccount extends ActiveRecord
{
    public static function tableName()
    {
        return 'whatsapp_accounts';
    }

    public function rules()
    {
        return [
            [['empresa_id', 'phone_number', 'session_name'], 'required'],
            [['empresa_id', 'user_id'], 'integer'],
            [['phone_number'], 'string', 'max' => 20],
            [['session_name', 'owner_jid'], 'string', 'max' => 255],
            [['is_connected', 'is_active'], 'boolean'],
            [['service_port'], 'integer'],
            [['last_connection', 'last_full_sync', 'created_at', 'updated_at'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'empresa_id' => 'Empresa',
            'user_id' => 'Usuário',
            'phone_number' => 'Número do Telefone',
            'session_name' => 'Nome da Sessão',
            'is_connected' => 'Conectado',
            'is_active' => 'Ativo',
            'last_connection' => 'Última Conexão',
            'last_full_sync' => 'Última Sincronização Completa',
            'created_at' => 'Criado em',
            'updated_at' => 'Atualizado em',
            'owner_jid' => 'JID do Proprietário',
        ];
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

    public function getChats()
    {
        return $this->hasMany(Chat::class, ['account_id' => 'id']);
    }

    public function getContacts()
    {
        return $this->hasMany(Contact::class, ['account_id' => 'id']);
    }

    public function getTickets()
    {
        return $this->hasMany(Ticket::class, ['account_id' => 'id']);
    }

    public function getAtendenteAccounts()
    {
        return $this->hasMany(AtendenteAccount::class, ['account_id' => 'id']);
    }

    public function getAtendentes()
    {
        return $this->hasMany(Atendente::class, ['id' => 'atendente_id'])
            ->viaTable('atendente_account', ['account_id' => 'id']);
    }

    /**
     * Retorna a URL do servico Node.js desta instancia.
     */
    public function getServiceUrl()
    {
        if ($this->service_port) {
            return 'http://localhost:' . $this->service_port;
        }
        return 'http://localhost:3000';
    }
}
