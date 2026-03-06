<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EvolutionApiService
{
    protected string $baseUrl;
    protected string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.evolution.url', 'http://localhost:8080'), '/');
        $this->apiKey = config('services.evolution.api_key', '');
    }

    protected function request(string $method, string $endpoint, array $data = []): array
    {
        $url = $this->baseUrl . $endpoint;

        try {
            $response = Http::withHeaders([
                'apikey' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->{$method}($url, $data);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Erro desconhecido',
                'status' => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('Evolution API Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    // === Instâncias ===

    public function createInstance(string $instanceName, array $options = []): array
    {
        return $this->request('post', '/instance/create', array_merge([
            'instanceName' => $instanceName,
            'qrcode' => true,
            'groups_ignore' => false,  // Receber mensagens de grupos
            'always_online' => false,
            'read_messages' => false,
            'read_status' => false,
            'reject_call' => false,
        ], $options));
    }

    public function deleteInstance(string $instanceName): array
    {
        return $this->request('delete', "/instance/delete/{$instanceName}");
    }

    public function getInstanceInfo(string $instanceName): array
    {
        return $this->request('get', "/instance/fetchInstances", [
            'instanceName' => $instanceName,
        ]);
    }

    public function getConnectionState(string $instanceName): array
    {
        return $this->request('get', "/instance/connectionState/{$instanceName}");
    }

    public function connectInstance(string $instanceName): array
    {
        return $this->request('get', "/instance/connect/{$instanceName}");
    }

    public function disconnectInstance(string $instanceName): array
    {
        return $this->request('delete', "/instance/logout/{$instanceName}");
    }

    public function restartInstance(string $instanceName): array
    {
        return $this->request('post', "/instance/restart/{$instanceName}");
    }

    public function updateInstanceSettings(string $instanceName, array $settings): array
    {
        // v1.7.x requer todos os campos
        $defaults = [
            'reject_call' => false,
            'groups_ignore' => false,
            'always_online' => false,
            'read_messages' => false,
            'read_status' => false,
            'sync_full_history' => false,
        ];

        return $this->request('post', "/settings/set/{$instanceName}", array_merge($defaults, $settings));
    }

    public function getInstanceSettings(string $instanceName): array
    {
        return $this->request('get', "/settings/find/{$instanceName}");
    }

    // === QR Code ===

    public function getQrCode(string $instanceName): array
    {
        return $this->request('get', "/instance/connect/{$instanceName}");
    }

    // === Mensagens ===

    public function sendText(string $instanceName, string $number, string $text): array
    {
        return $this->request('post', "/message/sendText/{$instanceName}", [
            'number' => $number,
            'text' => $text,
        ]);
    }

    public function sendMedia(string $instanceName, string $number, string $mediaType, string $mediaUrl, ?string $caption = null): array
    {
        return $this->request('post', "/message/sendMedia/{$instanceName}", [
            'number' => $number,
            'mediatype' => $mediaType,
            'media' => $mediaUrl,
            'caption' => $caption,
        ]);
    }

    public function sendImage(string $instanceName, string $number, string $imageUrl, ?string $caption = null): array
    {
        return $this->sendMedia($instanceName, $number, 'image', $imageUrl, $caption);
    }

    public function sendVideo(string $instanceName, string $number, string $videoUrl, ?string $caption = null): array
    {
        return $this->sendMedia($instanceName, $number, 'video', $videoUrl, $caption);
    }

    public function sendAudio(string $instanceName, string $number, string $audioUrl): array
    {
        return $this->request('post', "/message/sendWhatsAppAudio/{$instanceName}", [
            'number' => $number,
            'audio' => $audioUrl,
        ]);
    }

    public function sendDocument(string $instanceName, string $number, string $documentUrl, string $fileName): array
    {
        return $this->request('post', "/message/sendMedia/{$instanceName}", [
            'number' => $number,
            'mediatype' => 'document',
            'media' => $documentUrl,
            'fileName' => $fileName,
        ]);
    }

    public function sendLocation(string $instanceName, string $number, float $latitude, float $longitude, ?string $name = null): array
    {
        return $this->request('post', "/message/sendLocation/{$instanceName}", [
            'number' => $number,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'name' => $name,
        ]);
    }

    // === Histórico ===

    public function fetchMessages(string $instanceName, string $remoteJid, int $limit = 20): array
    {
        return $this->request('post', "/chat/fetchMessages/{$instanceName}", [
            'where' => [
                'key' => [
                    'remoteJid' => $remoteJid,
                ],
            ],
            'limit' => $limit,
        ]);
    }

    // === Chats ===

    public function fetchChats(string $instanceName): array
    {
        return $this->request('get', "/chat/fetchChats/{$instanceName}");
    }

    public function markAsRead(string $instanceName, string $remoteJid): array
    {
        return $this->request('post', "/chat/markMessageAsRead/{$instanceName}", [
            'readMessages' => [
                ['remoteJid' => $remoteJid],
            ],
        ]);
    }

    public function archiveChat(string $instanceName, string $remoteJid, bool $archive = true): array
    {
        return $this->request('post', "/chat/archiveChat/{$instanceName}", [
            'chat' => $remoteJid,
            'archive' => $archive,
        ]);
    }

    // === Contatos ===

    public function fetchContacts(string $instanceName): array
    {
        return $this->request('get', "/chat/fetchContacts/{$instanceName}");
    }

    public function getProfilePicture(string $instanceName, string $number): array
    {
        return $this->request('get', "/chat/fetchProfilePictureUrl/{$instanceName}", [
            'number' => $number,
        ]);
    }

    public function checkWhatsappNumber(string $instanceName, array $numbers): array
    {
        return $this->request('post', "/chat/whatsappNumbers/{$instanceName}", [
            'numbers' => $numbers,
        ]);
    }

    // === Webhooks ===

    public function setWebhook(string $instanceName, string $webhookUrl, array $events = []): array
    {
        return $this->request('post', "/webhook/set/{$instanceName}", [
            'webhook' => [
                'enabled' => true,
                'url' => $webhookUrl,
                'events' => $events ?: [
                    'QRCODE_UPDATED',
                    'MESSAGES_UPSERT',
                    'MESSAGES_UPDATE',
                    'MESSAGES_DELETE',
                    'SEND_MESSAGE',
                    'CONNECTION_UPDATE',
                    'PRESENCE_UPDATE',
                ],
            ],
        ]);
    }

    public function getWebhook(string $instanceName): array
    {
        return $this->request('get', "/webhook/find/{$instanceName}");
    }

    // === Grupos ===

    public function fetchGroups(string $instanceName): array
    {
        return $this->request('get', "/group/fetchAllGroups/{$instanceName}");
    }

    public function getGroupInfo(string $instanceName, string $groupJid): array
    {
        return $this->request('get', "/group/findGroupInfos/{$instanceName}", [
            'groupJid' => $groupJid,
        ]);
    }

    public function createGroup(string $instanceName, string $subject, array $participants): array
    {
        return $this->request('post', "/group/create/{$instanceName}", [
            'subject' => $subject,
            'participants' => $participants,
        ]);
    }

    // === Status da API ===

    public function healthCheck(): array
    {
        return $this->request('get', '/');
    }

    public function fetchAllInstances(): array
    {
        return $this->request('get', '/instance/fetchInstances');
    }
}
