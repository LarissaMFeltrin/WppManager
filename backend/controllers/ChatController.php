<?php

namespace backend\controllers;

use common\models\Atendente;
use common\models\Chat;
use common\models\Contact;
use common\models\Conversa;
use common\models\LogSistema;
use common\models\Message;
use common\models\User;
use Yii;
use yii\web\Response;

/**
 * ChatController - Painel de Conversas estilo WhatsApp Web
 */
class ChatController extends BaseController
{
    /**
     * Painel de conversas estilo WhatsApp Web.
     * Carrega lista de chats com ultima mensagem.
     * @return string
     */
    public function actionPainel()
    {
        $user = Yii::$app->user->identity;

        // Buscar atendente vinculado
        $atendente = Atendente::findOne(['user_id' => $user->id])
            ?: Atendente::findOne(['email' => $user->email]);

        // Contar fila para badge inicial (filtrado pelas instancias do atendente)
        $filaQuery = Conversa::find()
            ->andWhere(['status' => Conversa::STATUS_AGUARDANDO]);
        if ($atendente) {
            $accountIds = $atendente->getAccountIds();
            if (!empty($accountIds)) {
                $filaQuery->andWhere(['account_id' => $accountIds]);
            } else {
                $filaQuery->andWhere('1=0');
            }
        }
        $filaCount = $filaQuery->count();

        return $this->render('painel', [
            'atendente' => $atendente,
            'filaCount' => $filaCount,
        ]);
    }

    /**
     * Retorna mensagens de um chat via AJAX (JSON).
     * @param int $chat_id
     * @return array
     */
    public function actionMessages($chat_id, $after_id = null)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $chat = Chat::findOne($chat_id);
        if (!$chat) {
            return ['success' => false, 'error' => 'Chat nao encontrado.'];
        }

        // Verificar acesso por empresa
        if (!$this->isAdmin()) {
            $account = $chat->whatsappAccount;
            if (!$account || $account->empresa_id != $this->getEmpresaId()) {
                return ['success' => false, 'error' => 'Acesso negado.'];
            }
        }

        // Na carga inicial (sem after_id), sincronizar mensagens do WhatsApp
        // para pegar mensagens que possam ter sido perdidas durante restarts
        if (!$after_id) {
            try {
                $nodeUrl = $this->getServiceUrl($chat) . '/api/sync-chat/' . urlencode($chat->chat_id);
                $ctx = stream_context_create([
                    'http' => ['method' => 'POST', 'timeout' => 5, 'header' => 'Content-Type: application/json'],
                ]);
                @file_get_contents($nodeUrl, false, $ctx);
            } catch (\Throwable $e) {
                // Ignorar erros - sync é best-effort
            }
        }

        // Se after_id foi fornecido, buscar apenas novas mensagens
        if ($after_id) {
            $messages = Message::find()
                ->where(['chat_id' => $chat_id])
                ->andWhere(['>', 'id', (int)$after_id])
                ->orderBy(['timestamp' => SORT_ASC, 'id' => SORT_ASC])
                ->limit(100)
                ->all();
        } else {
            // Buscar as ultimas 200 mensagens (mais recentes)
            $ids = Message::find()
                ->select('id')
                ->where(['chat_id' => $chat_id])
                ->orderBy(['timestamp' => SORT_DESC, 'id' => SORT_DESC])
                ->limit(200)
                ->column();
            if (!empty($ids)) {
                $messages = Message::find()
                    ->where(['id' => $ids])
                    ->orderBy(['timestamp' => SORT_ASC, 'id' => SORT_ASC])
                    ->all();
            } else {
                $messages = [];
            }
        }

        // Coletar JIDs únicos para resolver nomes de contatos de uma vez
        $jids = [];
        $userIds = [];
        foreach ($messages as $msg) {
            if ($msg->from_jid) {
                $jids[$msg->from_jid] = true;
            }
            if ($msg->sent_by_user_id) {
                $userIds[$msg->sent_by_user_id] = true;
            }
        }
        $contactNames = [];
        if (!empty($jids)) {
            $contacts = Contact::find()
                ->select(['jid', 'name'])
                ->where(['jid' => array_keys($jids)])
                ->andWhere(['account_id' => $chat->account_id])
                ->andWhere(['not', ['name' => null]])
                ->asArray()
                ->all();
            foreach ($contacts as $c) {
                $contactNames[$c['jid']] = $c['name'];
            }
        }

        // Resolver nomes de atendentes que enviaram mensagens
        $userNames = [];
        if (!empty($userIds)) {
            $atendentes = Atendente::find()
                ->select(['user_id', 'nome'])
                ->where(['user_id' => array_keys($userIds)])
                ->asArray()
                ->all();
            foreach ($atendentes as $at) {
                $userNames[$at['user_id']] = $at['nome'];
            }
            // Fallback: buscar username do User se nao encontrou atendente
            $missingIds = array_diff(array_keys($userIds), array_keys($userNames));
            if (!empty($missingIds)) {
                $users = User::find()
                    ->select(['id', 'username'])
                    ->where(['id' => $missingIds])
                    ->asArray()
                    ->all();
                foreach ($users as $u) {
                    $userNames[$u['id']] = $u['username'];
                }
            }
        }

        $data = [];
        foreach ($messages as $msg) {
            // Resolver from_jid para nome de contato
            $senderName = null;
            if ($msg->from_jid) {
                $senderName = $contactNames[$msg->from_jid] ?? null;
                // Fallback: extrair número do JID
                if (!$senderName) {
                    $senderName = preg_replace('/@.*$/', '', $msg->from_jid);
                }
            }

            // Nome do atendente que enviou (se is_from_me e tem sent_by_user_id)
            $sentByName = null;
            if ($msg->sent_by_user_id && isset($userNames[$msg->sent_by_user_id])) {
                $sentByName = $userNames[$msg->sent_by_user_id];
            }

            $data[] = [
                'id' => $msg->id,
                'message_key' => $msg->message_key,
                'message_text' => $msg->message_text,
                'message_type' => $msg->message_type,
                'is_from_me' => (bool)$msg->is_from_me,
                'timestamp' => $msg->timestamp,
                'time_formatted' => $msg->timestamp ? date('H:i', $msg->timestamp) : ($msg->created_at ? Yii::$app->formatter->asTime($msg->created_at, 'short') : ''),
                'date_formatted' => $msg->timestamp ? date('d/m/Y', $msg->timestamp) : ($msg->created_at ? Yii::$app->formatter->asDate($msg->created_at, 'short') : ''),
                'status' => $msg->status,
                'media_url' => $msg->media_url,
                'media_mime_type' => $msg->media_mime_type,
                'from_jid' => $msg->from_jid,
                'sender_name' => $senderName,
                'sent_by_user_name' => $sentByName,
                'quoted_message_id' => $msg->quoted_message_id,
                'quoted_text' => $msg->quoted_text ?: $this->getQuotedFallbackText($msg->quoted_message_id),
                'is_edited' => (bool)($msg->is_edited ?? false),
                'is_deleted' => (bool)($msg->is_deleted ?? false),
                'reactions' => $msg->reactions ? json_decode($msg->reactions, true) : [],
            ];
        }

        // Buscar conversa vinculada a este chat para info de atendimento
        $conversa = Conversa::find()
            ->with('atendente')
            ->where(['chat_id' => $chat_id])
            ->andWhere(['!=', 'status', Conversa::STATUS_FINALIZADA])
            ->one();

        return [
            'success' => true,
            'chat_name' => $chat->chat_name ?: $chat->chat_id,
            'conversa_id' => $conversa ? $conversa->id : null,
            'conversa_status' => $conversa ? $conversa->status : null,
            'conversa_atendente' => $conversa && $conversa->atendente ? $conversa->atendente->nome : null,
            'messages' => $data,
        ];
    }

    /**
     * Lista de chats via AJAX (JSON) — usado pelo polling do painel.
     * @param string $q filtro de busca opcional
     * @return array
     */
    public function actionChatList($q = '')
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $user = Yii::$app->user->identity;
        $query = Chat::find()
            ->alias('ch')
            ->innerJoin('whatsapp_accounts wa', 'wa.id = ch.account_id')
            ->orderBy(['ch.last_message_timestamp' => SORT_DESC])
            ->limit(100);

        if (!$this->isAdmin()) {
            $query->andWhere(['wa.empresa_id' => $user->empresa_id]);

            // Filtrar por instancias vinculadas ao atendente
            $atendente = Atendente::findOne(['user_id' => $user->id])
                ?: Atendente::findOne(['email' => $user->email]);
            if ($atendente) {
                $accountIds = $atendente->getAccountIds();
                if (!empty($accountIds)) {
                    $query->andWhere(['ch.account_id' => $accountIds]);
                }
            }
        }

        if (!empty($q)) {
            $query->andWhere(['or',
                ['like', 'ch.chat_name', $q],
                ['like', 'ch.chat_id', $q],
            ]);
        }

        $chats = $query->all();
        $data = [];
        foreach ($chats as $chat) {
            // Buscar última mensagem
            $lastMsg = Message::find()
                ->where(['chat_id' => $chat->id])
                ->orderBy(['id' => SORT_DESC])
                ->one();

            $lastMsgText = '';
            $lastMsgFromMe = false;
            if ($lastMsg) {
                $lastMsgFromMe = (bool)$lastMsg->is_from_me;
                $lastMsgText = $lastMsg->message_text ?: '[' . $lastMsg->message_type . ']';
                if (mb_strlen($lastMsgText) > 45) {
                    $lastMsgText = mb_substr($lastMsgText, 0, 45) . '...';
                }
            }

            $data[] = [
                'id' => $chat->id,
                'chat_name' => $chat->chat_name ?: $chat->chat_id,
                'chat_id' => $chat->chat_id,
                'chat_type' => $chat->chat_type ?: 'individual',
                'unread_count' => (int)$chat->unread_count,
                'last_message_timestamp' => $chat->last_message_timestamp,
                'last_time' => $chat->last_message_timestamp ? date('H:i', $chat->last_message_timestamp) : '',
                'last_msg_text' => $lastMsgText,
                'last_msg_from_me' => $lastMsgFromMe,
            ];
        }

        return ['success' => true, 'chats' => $data];
    }

    /**
     * Envia mensagem de texto via Node.js WhatsApp service.
     * POST: chat_id, text
     * @return array
     */
    public function actionSendMessage()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!Yii::$app->request->isPost) {
            return ['success' => false, 'error' => 'Método não permitido.'];
        }

        $chatId = Yii::$app->request->post('chat_id');
        $text = trim(Yii::$app->request->post('text', ''));

        if (!$chatId || $text === '') {
            return ['success' => false, 'error' => 'chat_id e text são obrigatórios.'];
        }

        $chat = Chat::findOne($chatId);
        if (!$chat) {
            return ['success' => false, 'error' => 'Chat não encontrado.'];
        }

        // Verificar acesso por empresa
        if (!$this->isAdmin()) {
            $account = $chat->whatsappAccount;
            if (!$account || $account->empresa_id != $this->getEmpresaId()) {
                return ['success' => false, 'error' => 'Acesso negado.'];
            }
        }

        // Chamar Node.js service para enviar a mensagem
        $quotedMessageKey = Yii::$app->request->post('quoted_message_key', '');
        $userId = Yii::$app->user->id;
        $nodeUrl = $this->getServiceUrl($chat) . '/api/send-message';
        $payload = json_encode([
            'jid' => $chat->chat_id,
            'text' => $text,
            'quotedMessageId' => $quotedMessageKey ?: null,
            'sentByUserId' => $userId,
        ]);

        $ch = curl_init($nodeUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            LogSistema::gravar(LogSistema::TIPO_ERRO, LogSistema::NIVEL_ERROR,
                "Erro ao enviar mensagem para {$chat->chat_name}: {$curlError}",
                ['chat_id' => $chatId, 'user_id' => $userId]
            );
            return ['success' => false, 'error' => 'Erro de conexão com WhatsApp service: ' . $curlError];
        }

        $result = json_decode($response, true);
        if ($httpCode !== 200 || !$result || !$result['success']) {
            LogSistema::gravar(LogSistema::TIPO_ERRO, LogSistema::NIVEL_ERROR,
                "Falha ao enviar mensagem para {$chat->chat_name}: " . ($result['error'] ?? 'HTTP ' . $httpCode),
                ['chat_id' => $chatId, 'user_id' => $userId, 'http_code' => $httpCode]
            );
            return ['success' => false, 'error' => $result['error'] ?? 'Erro ao enviar mensagem.'];
        }

        // Atualizar ultimo_acesso do atendente
        $this->updateAtendenteAccess();

        return ['success' => true, 'messageId' => $result['messageId'] ?? null];
    }

    /**
     * Envia arquivo/imagem via Node.js WhatsApp service.
     * POST multipart: chat_id, file, caption (opcional)
     * @return array
     */
    public function actionSendMedia()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!Yii::$app->request->isPost) {
            return ['success' => false, 'error' => 'Método não permitido.'];
        }

        $chatId = Yii::$app->request->post('chat_id');
        $caption = Yii::$app->request->post('caption', '');

        if (!$chatId) {
            return ['success' => false, 'error' => 'chat_id é obrigatório.'];
        }

        $file = \yii\web\UploadedFile::getInstanceByName('file');
        if (!$file) {
            return ['success' => false, 'error' => 'Nenhum arquivo enviado.'];
        }

        $chat = Chat::findOne($chatId);
        if (!$chat) {
            return ['success' => false, 'error' => 'Chat não encontrado.'];
        }

        // Verificar acesso por empresa
        if (!$this->isAdmin()) {
            $account = $chat->whatsappAccount;
            if (!$account || $account->empresa_id != $this->getEmpresaId()) {
                return ['success' => false, 'error' => 'Acesso negado.'];
            }
        }

        // Enviar arquivo para Node.js service via multipart
        $nodeUrl = $this->getServiceUrl($chat) . '/api/send-media';
        $tmpPath = $file->tempName;

        $ch = curl_init($nodeUrl);
        $postData = [
            'jid' => $chat->chat_id,
            'caption' => $caption,
            'sentByUserId' => (string)Yii::$app->user->id,
            'file' => new \CURLFile($tmpPath, $file->type, $file->name),
        ];
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return ['success' => false, 'error' => 'Erro de conexão: ' . $curlError];
        }

        $result = json_decode($response, true);
        if ($httpCode !== 200 || !$result || !$result['success']) {
            return ['success' => false, 'error' => $result['error'] ?? 'Erro ao enviar arquivo.'];
        }

        $this->updateAtendenteAccess();

        return ['success' => true, 'messageId' => $result['messageId'] ?? null];
    }

    /**
     * Editar mensagem enviada via Node.js WhatsApp service.
     * POST: chat_id, message_key, new_text
     * @return array
     */
    public function actionEditMessage()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!Yii::$app->request->isPost) {
            return ['success' => false, 'error' => 'Método não permitido.'];
        }

        $chatId = Yii::$app->request->post('chat_id');
        $messageKey = Yii::$app->request->post('message_key');
        $newText = trim(Yii::$app->request->post('new_text', ''));

        if (!$chatId || !$messageKey || $newText === '') {
            return ['success' => false, 'error' => 'chat_id, message_key e new_text são obrigatórios.'];
        }

        $chat = Chat::findOne($chatId);
        if (!$chat) {
            return ['success' => false, 'error' => 'Chat não encontrado.'];
        }

        if (!$this->isAdmin()) {
            $account = $chat->whatsappAccount;
            if (!$account || $account->empresa_id != $this->getEmpresaId()) {
                return ['success' => false, 'error' => 'Acesso negado.'];
            }
        }

        $nodeUrl = $this->getServiceUrl($chat) . '/api/edit-message';
        $payload = json_encode([
            'jid' => $chat->chat_id,
            'messageId' => $messageKey,
            'newText' => $newText,
        ]);

        $ch = curl_init($nodeUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true);
        if ($httpCode !== 200 || !$result || !$result['success']) {
            return ['success' => false, 'error' => $result['error'] ?? 'Erro ao editar mensagem.'];
        }

        return ['success' => true];
    }

    /**
     * Reagir a uma mensagem via Node.js WhatsApp service.
     * POST: chat_id, message_key, emoji
     * @return array
     */
    public function actionReactMessage()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!Yii::$app->request->isPost) {
            return ['success' => false, 'error' => 'Método não permitido.'];
        }

        $chatId = Yii::$app->request->post('chat_id');
        $messageKey = Yii::$app->request->post('message_key');
        $emoji = Yii::$app->request->post('emoji', '');

        if (!$chatId || !$messageKey) {
            return ['success' => false, 'error' => 'chat_id e message_key são obrigatórios.'];
        }

        $chat = Chat::findOne($chatId);
        if (!$chat) {
            return ['success' => false, 'error' => 'Chat não encontrado.'];
        }

        if (!$this->isAdmin()) {
            $account = $chat->whatsappAccount;
            if (!$account || $account->empresa_id != $this->getEmpresaId()) {
                return ['success' => false, 'error' => 'Acesso negado.'];
            }
        }

        $nodeUrl = $this->getServiceUrl($chat) . '/api/react-message';
        $payload = json_encode([
            'jid' => $chat->chat_id,
            'messageId' => $messageKey,
            'emoji' => $emoji,
        ]);

        $ch = curl_init($nodeUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true);
        if ($httpCode !== 200 || !$result || !$result['success']) {
            return ['success' => false, 'error' => $result['error'] ?? 'Erro ao reagir.'];
        }

        return ['success' => true];
    }

    /**
     * Excluir uma mensagem enviada (para todos) via Node.js WhatsApp service.
     * POST: chat_id, message_key
     * @return array
     */
    public function actionDeleteMessage()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!Yii::$app->request->isPost) {
            return ['success' => false, 'error' => 'Método não permitido.'];
        }

        $chatId = Yii::$app->request->post('chat_id');
        $messageKey = Yii::$app->request->post('message_key');

        if (!$chatId || !$messageKey) {
            return ['success' => false, 'error' => 'chat_id e message_key são obrigatórios.'];
        }

        $chat = Chat::findOne($chatId);
        if (!$chat) {
            return ['success' => false, 'error' => 'Chat não encontrado.'];
        }

        if (!$this->isAdmin()) {
            $account = $chat->whatsappAccount;
            if (!$account || $account->empresa_id != $this->getEmpresaId()) {
                return ['success' => false, 'error' => 'Acesso negado.'];
            }
        }

        $nodeUrl = $this->getServiceUrl($chat) . '/api/delete-message';
        $payload = json_encode([
            'jid' => $chat->chat_id,
            'messageId' => $messageKey,
        ]);

        $ch = curl_init($nodeUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true);
        if ($httpCode !== 200 || !$result || !$result['success']) {
            return ['success' => false, 'error' => $result['error'] ?? 'Erro ao excluir mensagem.'];
        }

        return ['success' => true];
    }

    /**
     * Encaminhar mensagem para outro chat via Node.js WhatsApp service.
     * POST: from_chat_id, to_chat_id, message_key
     * @return array
     */
    public function actionForwardMessage()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!Yii::$app->request->isPost) {
            return ['success' => false, 'error' => 'Método não permitido.'];
        }

        $fromChatId = Yii::$app->request->post('from_chat_id');
        $toChatId = Yii::$app->request->post('to_chat_id');
        $messageKey = Yii::$app->request->post('message_key');

        if (!$fromChatId || !$toChatId || !$messageKey) {
            return ['success' => false, 'error' => 'from_chat_id, to_chat_id e message_key são obrigatórios.'];
        }

        $fromChat = Chat::findOne($fromChatId);
        $toChat = Chat::findOne($toChatId);
        if (!$fromChat || !$toChat) {
            return ['success' => false, 'error' => 'Chat não encontrado.'];
        }

        $nodeUrl = $this->getServiceUrl($fromChat) . '/api/forward-message';
        $payload = json_encode([
            'fromJid' => $fromChat->chat_id,
            'toJid' => $toChat->chat_id,
            'messageId' => $messageKey,
        ]);

        $ch = curl_init($nodeUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true);
        if ($httpCode !== 200 || !$result || !$result['success']) {
            return ['success' => false, 'error' => $result['error'] ?? 'Erro ao encaminhar mensagem.'];
        }

        return ['success' => true];
    }

    /**
     * Atualizar nome do contato/cliente.
     * POST: chat_id, name
     * @return array
     */
    public function actionUpdateContactName()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!Yii::$app->request->isPost) {
            return ['success' => false, 'error' => 'Método não permitido.'];
        }

        $chatId = Yii::$app->request->post('chat_id');
        $name = trim(Yii::$app->request->post('name', ''));

        if (!$chatId || $name === '') {
            return ['success' => false, 'error' => 'chat_id e name são obrigatórios.'];
        }

        $chat = Chat::findOne($chatId);
        if (!$chat) {
            return ['success' => false, 'error' => 'Chat não encontrado.'];
        }

        // Atualizar nome do chat
        $chat->chat_name = $name;
        $chat->save(false);

        // Atualizar contato vinculado (pelo JID)
        Contact::updateAll(
            ['name' => $name, 'updated_at' => date('Y-m-d H:i:s')],
            ['jid' => $chat->chat_id, 'account_id' => $chat->account_id]
        );

        // Atualizar conversas abertas deste chat
        Conversa::updateAll(
            ['cliente_nome' => $name, 'updated_at' => date('Y-m-d H:i:s')],
            ['chat_id' => $chat->id, 'status' => [Conversa::STATUS_AGUARDANDO, Conversa::STATUS_EM_ATENDIMENTO]]
        );

        return ['success' => true, 'name' => $name];
    }

    /**
     * Retorna texto descritivo para mensagem citada quando quoted_text é null.
     */
    private function getQuotedFallbackText($quotedMessageId)
    {
        if (!$quotedMessageId) return null;

        $quoted = Message::find()
            ->select(['message_type', 'message_text'])
            ->where(['message_key' => $quotedMessageId])
            ->asArray()
            ->one();

        if (!$quoted) return null;
        if ($quoted['message_text']) return $quoted['message_text'];

        $labels = [
            'image' => "\xF0\x9F\x96\xBC\xEF\xB8\x8F Imagem",
            'video' => "\xF0\x9F\x8E\xA5 Video",
            'audio' => "\xF0\x9F\x8E\xA4 Audio",
            'document' => "\xF0\x9F\x93\x84 Documento",
            'sticker' => "\xF0\x9F\xAA\xA7 Sticker",
            'location' => "\xF0\x9F\x93\x8D Localizacao",
            'contact' => "\xF0\x9F\x93\x8C Contato",
        ];

        return $labels[$quoted['message_type']] ?? 'Mensagem';
    }

    /**
     * Retorna a URL do servico Node.js para um dado Chat.
     */
    private function getServiceUrl(Chat $chat): string
    {
        $account = $chat->whatsappAccount;
        if ($account && $account->service_port) {
            return 'http://localhost:' . $account->service_port;
        }
        return 'http://localhost:3000';
    }

    /**
     * Atualiza ultimo_acesso do atendente vinculado ao usuario logado.
     */
    private function updateAtendenteAccess()
    {
        $userId = Yii::$app->user->id;
        $atendente = Atendente::findOne(['user_id' => $userId]);
        if (!$atendente) {
            $email = Yii::$app->user->identity->email ?? null;
            if ($email) {
                $atendente = Atendente::findOne(['email' => $email]);
            }
        }
        if ($atendente) {
            $atendente->ultimo_acesso = date('Y-m-d H:i:s');
            $atendente->status = Atendente::STATUS_ONLINE;
            $atendente->save(false);
        }
    }
}
