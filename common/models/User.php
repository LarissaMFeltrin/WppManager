<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

class User extends ActiveRecord implements IdentityInterface
{
    const ROLE_ADMIN = 'admin';
    const ROLE_SUPERVISOR = 'supervisor';
    const ROLE_AGENT = 'agent';

    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 0;

    public static function tableName()
    {
        return 'users';
    }

    public function rules()
    {
        return [
            [['username', 'email'], 'required'],
            [['username', 'email', 'password_hash', 'auth_token'], 'string', 'max' => 255],
            [['email'], 'email'],
            [['email'], 'unique'],
            [['username'], 'unique'],
            [['status', 'empresa_id'], 'integer'],
            [['role'], 'in', 'range' => [self::ROLE_ADMIN, self::ROLE_SUPERVISOR, self::ROLE_AGENT]],
            [['role'], 'default', 'value' => self::ROLE_AGENT],
            [['status'], 'default', 'value' => self::STATUS_ACTIVE],
            [['token_expires_at', 'created_at', 'updated_at'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'empresa_id' => 'Empresa',
            'username' => 'Usuário',
            'email' => 'E-mail',
            'password_hash' => 'Senha',
            'status' => 'Status',
            'role' => 'Perfil',
            'created_at' => 'Criado em',
            'updated_at' => 'Atualizado em',
        ];
    }

    // IdentityInterface

    public static function findIdentity($id)
    {
        return static::findOne(['id' => $id, 'status' => self::STATUS_ACTIVE]);
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        return static::findOne(['auth_token' => $token, 'status' => self::STATUS_ACTIVE]);
    }

    public static function findByUsername($username)
    {
        return static::findOne(['username' => $username, 'status' => self::STATUS_ACTIVE]);
    }

    public static function findByEmail($email)
    {
        return static::findOne(['email' => $email, 'status' => self::STATUS_ACTIVE]);
    }

    public function getId()
    {
        return $this->getPrimaryKey();
    }

    public function getAuthKey()
    {
        return $this->auth_token;
    }

    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    public function setPassword($password)
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    public function generateAuthKey()
    {
        $this->auth_token = Yii::$app->security->generateRandomString();
    }

    // Roles

    public function isAdmin()
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isSupervisor()
    {
        return $this->role === self::ROLE_SUPERVISOR;
    }

    public function isAgent()
    {
        return $this->role === self::ROLE_AGENT;
    }

    // Relations

    public function getEmpresa()
    {
        return $this->hasOne(Empresa::class, ['id' => 'empresa_id']);
    }

    public function getAtendente()
    {
        return $this->hasOne(Atendente::class, ['user_id' => 'id']);
    }

    public function getWhatsappAccounts()
    {
        return $this->hasMany(WhatsappAccount::class, ['user_id' => 'id']);
    }
}
