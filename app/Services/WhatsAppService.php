<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Serviço unificado de WhatsApp
 *
 * Estratégia Complementar:
 * - Evolution API: operações básicas (enviar msg, webhooks, instâncias)
 * - Baileys: funcionalidades extras (reagir, deletar, editar, encaminhar)
 */
class WhatsAppService
{
    protected EvolutionApiService $evolution;
    protected BaileysService $baileys;
    protected bool $baileysEnabled;

    public function __construct(EvolutionApiService $evolution, BaileysService $baileys)
    {
        $this->evolution = $evolution;
        $this->baileys = $baileys;
        $this->baileysEnabled = config('services.baileys.enabled', false);
    }

    /**
     * Verificar se Baileys está disponível
     */
    public function isBaileysAvailable(): bool
    {
        if (!$this->baileysEnabled) {
            return false;
        }

        try {
            $health = $this->baileys->health();
            return $health['success'] && in_array($health['data']['status'] ?? '', ['connected', 'qr_pending']);
        } catch (\Exception $e) {
            return false;
        }
    }

    // ==========================================
    // EVOLUTION API (operações básicas)
    // ==========================================

    public function sendText(string $instance, string $number, string $text): array
    {
        return $this->evolution->sendText($instance, $number, $text);
    }

    public function sendImage(string $instance, string $number, string $imageUrl, ?string $caption = null): array
    {
        return $this->evolution->sendImage($instance, $number, $imageUrl, $caption);
    }

    public function sendDocument(string $instance, string $number, string $documentUrl, string $fileName): array
    {
        return $this->evolution->sendDocument($instance, $number, $documentUrl, $fileName);
    }

    public function sendAudio(string $instance, string $number, string $audioUrl): array
    {
        return $this->evolution->sendAudio($instance, $number, $audioUrl);
    }

    public function fetchContacts(string $instance): array
    {
        return $this->evolution->fetchContacts($instance);
    }

    public function markAsRead(string $instance, string $remoteJid): array
    {
        return $this->evolution->markAsRead($instance, $remoteJid);
    }

    public function sendTyping(string $instance, string $number): array
    {
        return $this->evolution->sendPresence($instance, $number, 'composing');
    }

    // ==========================================
    // BAILEYS (funcionalidades extras)
    // ==========================================

    /**
     * Reagir a mensagem com emoji
     */
    public function reactMessage(string $jid, string $messageId, string $emoji): array
    {
        if (!$this->isBaileysAvailable()) {
            return ['success' => false, 'error' => 'Serviço Baileys não disponível'];
        }

        return $this->baileys->reactMessage($jid, $messageId, $emoji);
    }

    /**
     * Deletar mensagem para todos
     */
    public function deleteMessage(string $jid, string $messageId): array
    {
        if (!$this->isBaileysAvailable()) {
            return ['success' => false, 'error' => 'Serviço Baileys não disponível'];
        }

        return $this->baileys->deleteMessage($jid, $messageId);
    }

    /**
     * Editar mensagem enviada
     */
    public function editMessage(string $jid, string $messageId, string $newText): array
    {
        if (!$this->isBaileysAvailable()) {
            return ['success' => false, 'error' => 'Serviço Baileys não disponível'];
        }

        return $this->baileys->editMessage($jid, $messageId, $newText);
    }

    /**
     * Encaminhar mensagem
     */
    public function forwardMessage(string $fromJid, string $toJid, string $messageId, ?int $sentByUserId = null): array
    {
        if (!$this->isBaileysAvailable()) {
            return ['success' => false, 'error' => 'Serviço Baileys não disponível'];
        }

        return $this->baileys->forwardMessage($fromJid, $toJid, $messageId, $sentByUserId);
    }

    /**
     * Responder mensagem com citação (quote)
     */
    public function replyMessage(string $jid, string $text, string $quotedMessageId, ?int $sentByUserId = null): array
    {
        if (!$this->isBaileysAvailable()) {
            return ['success' => false, 'error' => 'Serviço Baileys não disponível'];
        }

        return $this->baileys->sendText($jid, $text, $quotedMessageId, $sentByUserId);
    }

    // ==========================================
    // STATUS
    // ==========================================

    public function getStatus(): array
    {
        $evolution = ['enabled' => true, 'connected' => false];
        $baileys = ['enabled' => $this->baileysEnabled, 'connected' => false];

        try {
            $health = $this->evolution->healthCheck();
            $evolution['connected'] = $health['success'];
        } catch (\Exception $e) {}

        if ($this->baileysEnabled) {
            try {
                $health = $this->baileys->health();
                $baileys['connected'] = $health['success'] && ($health['data']['status'] ?? '') === 'connected';
                $baileys['status'] = $health['data']['status'] ?? 'unknown';
            } catch (\Exception $e) {}
        }

        return compact('evolution', 'baileys');
    }
}
