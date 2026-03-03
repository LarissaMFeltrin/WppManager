<?php

namespace console\controllers;

use common\models\User;
use common\models\Chat;
use common\models\Message;
use common\models\Empresa;
use common\models\WhatsappAccount;
use common\models\Contact;
use yii\console\Controller;

class TestController extends Controller
{
    public function actionDb()
    {
        $user = User::findByUsername('mauro');
        if ($user) {
            echo "User: {$user->username} | role={$user->role} | empresa_id={$user->empresa_id}\n";
            echo "Password OK: " . ($user->validatePassword('admin123') ? 'SIM' : 'NAO') . "\n";
        } else {
            echo "User NOT FOUND!\n";
            return;
        }

        echo "Chats: " . Chat::find()->count() . "\n";
        echo "Messages: " . Message::find()->count() . "\n";
        echo "Empresas: " . Empresa::find()->count() . "\n";
        echo "WhatsApp Accounts: " . WhatsappAccount::find()->count() . "\n";
        echo "Contacts: " . Contact::find()->count() . "\n";

        echo "\nTudo OK!\n";
    }

    public function actionVerifyLogin()
    {
        $user = User::findByUsername('larisa');
        if ($user) {
            echo "Encontrado: {$user->username} | role={$user->role} | status={$user->status}\n";
            echo "Password 'lmf' OK: " . ($user->validatePassword('lmf') ? 'SIM' : 'NAO') . "\n";
        } else {
            echo "NAO ENCONTRADO (findByUsername busca status=1)\n";
        }
    }

    public function actionCreateUser()
    {
        // Verificar se já existe
        $existing = User::findOne(['username' => 'larisa']);
        if ($existing) {
            echo "Usuário 'larisa' já existe (id={$existing->id}).\n";
            return;
        }

        $user = new User();
        $user->username = 'larisa';
        $user->email = 'larisa@wpp-manager.local';
        $user->setPassword('lmf');
        $user->generateAuthKey();
        $user->role = User::ROLE_ADMIN;
        $user->status = User::STATUS_ACTIVE;
        $user->empresa_id = 1;

        if ($user->save()) {
            echo "Usuário 'larisa' criado com sucesso! id={$user->id}\n";
            echo "Login: larisa / lmf\n";
        } else {
            echo "ERRO ao criar usuário:\n";
            foreach ($user->getErrors() as $field => $errors) {
                echo "  {$field}: " . implode(', ', $errors) . "\n";
            }
        }
    }
}
