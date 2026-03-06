<?php

namespace App\Filament\Pages;

use App\Events\NewMessageReceived;
use App\Models\Chat;
use App\Models\Conversa;
use App\Models\Message;
use App\Services\EvolutionApiService;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class ChatAtendimento extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-computer-desktop';
    protected static ?string $navigationLabel = 'Meu Console';
    protected static ?string $navigationGroup = 'Atendimento';
    protected static ?int $navigationSort = 3;
    protected static string $view = 'filament.pages.chat-atendimento';

    public ?int $conversaId = null;
    public ?Conversa $conversa = null;
    public ?Chat $chat = null;
    public array $messages = [];
    public string $messageText = '';
    public string $activeTab = 'fila';
    public array $conversasAguardando = [];
    public array $minhasConversas = [];

    public function mount(): void
    {
        $this->loadConversas();

        // Verificar se tem conversa na URL
        $conversaId = request()->query('conversa');
        if ($conversaId) {
            $this->selectConversa($conversaId);
        }
    }

    public function loadConversas(): void
    {
        $user = Auth::user();

        // Conversas aguardando
        $this->conversasAguardando = Conversa::with(['chat', 'account'])
            ->where('status', 'aguardando')
            ->whereHas('account', fn($q) => $q->where('empresa_id', $user->empresa_id))
            ->orderBy('cliente_aguardando_desde', 'asc')
            ->get()
            ->toArray();

        // Minhas conversas em atendimento
        $this->minhasConversas = Conversa::with(['chat', 'account'])
            ->where('status', 'em_atendimento')
            ->whereHas('atendente', fn($q) => $q->where('user_id', $user->id))
            ->orderBy('ultima_msg_em', 'desc')
            ->get()
            ->toArray();
    }

    public function selectConversa(int $conversaId): void
    {
        $this->conversaId = $conversaId;
        $this->conversa = Conversa::with(['chat', 'account', 'atendente'])->find($conversaId);

        if ($this->conversa && $this->conversa->chat) {
            $this->chat = $this->conversa->chat;
            $this->loadMessages();
        }
    }

    public function loadMessages(): void
    {
        if (!$this->chat) {
            $this->messages = [];
            return;
        }

        $this->messages = Message::where('chat_id', $this->chat->id)
            ->orderBy('timestamp', 'asc')
            ->get()
            ->toArray();

        // Marcar como lido
        $this->chat->update(['unread_count' => 0]);
    }

    public function atenderConversa(int $conversaId): void
    {
        $conversa = Conversa::find($conversaId);
        $user = Auth::user();

        if (!$conversa) {
            Notification::make()->title('Conversa não encontrada')->danger()->send();
            return;
        }

        // Buscar atendente do usuário
        $atendente = $user->atendente;
        if (!$atendente) {
            Notification::make()->title('Você não está cadastrado como atendente')->danger()->send();
            return;
        }

        $conversa->update([
            'atendente_id' => $atendente->id,
            'status' => 'em_atendimento',
            'atendida_em' => now(),
            'cliente_aguardando_desde' => null,
        ]);

        $this->loadConversas();
        $this->selectConversa($conversaId);

        Notification::make()->title('Conversa assumida com sucesso')->success()->send();
    }

    public function sendMessage(): void
    {
        if (empty(trim($this->messageText)) || !$this->conversa || !$this->chat) {
            return;
        }

        try {
            $account = $this->conversa->account;
            $evolutionApi = app(EvolutionApiService::class);

            // Enviar via Evolution API
            $response = $evolutionApi->sendText(
                $account->session_name,
                $this->chat->chat_id,
                $this->messageText
            );

            if ($response['success'] && isset($response['data']['key']['id'])) {
                // Salvar mensagem no banco
                $message = Message::create([
                    'chat_id' => $this->chat->id,
                    'message_key' => $response['data']['key']['id'],
                    'from_jid' => $account->owner_jid,
                    'to_jid' => $this->chat->chat_id,
                    'message_text' => $this->messageText,
                    'message_type' => 'text',
                    'is_from_me' => true,
                    'timestamp' => time(),
                    'status' => 'sent',
                ]);

                // Atualizar conversa
                $this->conversa->update(['ultima_msg_em' => now()]);

                // Broadcast para outros usuários
                broadcast(new NewMessageReceived($message, $account->id, $this->chat->id))->toOthers();

                $this->messageText = '';
                $this->loadMessages();
            } else {
                throw new \Exception($response['error'] ?? 'Erro ao enviar mensagem');
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Erro ao enviar mensagem')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function finalizarConversa(): void
    {
        if (!$this->conversa) {
            return;
        }

        $this->conversa->update([
            'status' => 'finalizada',
            'finalizada_em' => now(),
        ]);

        Notification::make()->title('Conversa finalizada')->success()->send();

        $this->conversaId = null;
        $this->conversa = null;
        $this->chat = null;
        $this->messages = [];
        $this->loadConversas();
    }

    public function devolverConversa(): void
    {
        if (!$this->conversa) {
            return;
        }

        $this->conversa->update([
            'status' => 'aguardando',
            'atendente_id' => null,
            'devolvida_por' => $this->conversa->atendente_id,
            'cliente_aguardando_desde' => now(),
        ]);

        Notification::make()->title('Conversa devolvida à fila')->warning()->send();

        $this->conversaId = null;
        $this->conversa = null;
        $this->chat = null;
        $this->messages = [];
        $this->loadConversas();
    }

    public function onNewMessage($event): void
    {
        // Recarregar mensagens se for do chat atual
        if ($this->chat && $event['chat_id'] == $this->chat->id) {
            $this->loadMessages();
        }

        // Recarregar lista de conversas
        $this->loadConversas();
    }

    public function getListeners(): array
    {
        $listeners = [];

        // Escutar eventos de todas as accounts da empresa do usuário
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        if ($user && $user->empresa_id) {
            $accounts = \App\Models\WhatsappAccount::where('empresa_id', $user->empresa_id)->pluck('id');
            foreach ($accounts as $accountId) {
                $listeners["echo-private:account.{$accountId},message.new"] = 'onNewMessage';
            }
        }

        return $listeners;
    }
}
