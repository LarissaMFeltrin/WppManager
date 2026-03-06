<?php

use App\Models\Chat;
use App\Models\WhatsappAccount;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Canal para mensagens de um chat específico
Broadcast::channel('chat.{chatId}', function ($user, $chatId) {
    $chat = Chat::find($chatId);
    if (!$chat) return false;

    // Verificar se o usuário tem acesso a este chat via account
    return $chat->account && $chat->account->empresa_id === $user->empresa_id;
});

// Canal para todas as mensagens de uma conta WhatsApp
Broadcast::channel('account.{accountId}', function ($user, $accountId) {
    $account = WhatsappAccount::find($accountId);
    if (!$account) return false;

    return $account->empresa_id === $user->empresa_id;
});
