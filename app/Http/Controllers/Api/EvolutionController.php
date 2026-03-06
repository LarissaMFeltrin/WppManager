<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WhatsappAccount;
use App\Services\EvolutionApiService;
use Illuminate\Http\Request;

class EvolutionController extends Controller
{
    protected EvolutionApiService $api;

    public function __construct(EvolutionApiService $api)
    {
        $this->api = $api;
    }

    public function createInstance(Request $request)
    {
        $request->validate([
            'instanceName' => 'required|string',
            'number' => 'nullable|string',
        ]);

        $instanceName = $request->input('instanceName');
        $number = $request->input('number');

        // Criar instância na Evolution API
        $result = $this->api->createInstance($instanceName, [
            'number' => $number,
            'qrcode' => true,
            'integration' => 'WHATSAPP-BAILEYS',
        ]);

        if ($result['success'] && isset($result['data']['qrcode']['base64'])) {
            return response()->json([
                'success' => true,
                'qrcode' => $result['data']['qrcode']['base64'],
            ]);
        }

        // Se já existe, tenta conectar
        if (isset($result['error']) && str_contains($result['error'], 'already')) {
            return $this->getQrCode($instanceName);
        }

        return response()->json([
            'success' => false,
            'error' => $result['error'] ?? 'Erro ao criar instância',
        ], 400);
    }

    public function getQrCode(string $instanceName)
    {
        // Primeiro verifica o status
        $status = $this->api->getConnectionState($instanceName);

        if (isset($status['data']['state']) && $status['data']['state'] === 'open') {
            // Já está conectado
            $this->updateLocalStatus($instanceName, true);

            return response()->json([
                'connected' => true,
                'qrcode' => null,
            ]);
        }

        // Tenta obter QR Code
        $result = $this->api->connectInstance($instanceName);

        if (isset($result['data']['qrcode']['base64'])) {
            return response()->json([
                'connected' => false,
                'qrcode' => $result['data']['qrcode']['base64'],
            ]);
        }

        // Tenta pegar do campo code se não tiver base64
        if (isset($result['data']['code'])) {
            return response()->json([
                'connected' => false,
                'qrcode' => 'data:image/png;base64,' . $result['data']['code'],
            ]);
        }

        return response()->json([
            'connected' => false,
            'qrcode' => null,
            'error' => 'QR Code não disponível',
        ]);
    }

    public function getStatus(string $instanceName)
    {
        $result = $this->api->getConnectionState($instanceName);

        $isConnected = isset($result['data']['state']) && $result['data']['state'] === 'open';

        if ($isConnected) {
            $this->updateLocalStatus($instanceName, true);
        }

        // Se não está conectado, tenta pegar novo QR Code
        $qrcode = null;
        if (!$isConnected) {
            $qrResult = $this->api->connectInstance($instanceName);
            if (isset($qrResult['data']['qrcode']['base64'])) {
                $qrcode = $qrResult['data']['qrcode']['base64'];
            }
        }

        return response()->json([
            'connected' => $isConnected,
            'state' => $result['data']['state'] ?? 'unknown',
            'qrcode' => $qrcode,
        ]);
    }

    protected function updateLocalStatus(string $instanceName, bool $connected): void
    {
        WhatsappAccount::where('session_name', $instanceName)->update([
            'is_connected' => $connected,
            'last_connection' => $connected ? now() : null,
        ]);
    }
}
