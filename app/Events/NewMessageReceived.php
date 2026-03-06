<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class NewMessageReceived implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public array $messageData;
    public int $accountId;
    public int $chatId;

    public function __construct(Message $message, int $accountId, int $chatId)
    {
        $this->accountId = $accountId;
        $this->chatId = $chatId;
        $this->messageData = [
            'id' => $message->id,
            'chat_id' => $chatId,
            'message_key' => $message->message_key,
            'message_text' => $message->message_text,
            'message_type' => $message->message_type,
            'is_from_me' => $message->is_from_me,
            'from_jid' => $message->from_jid,
            'timestamp' => $message->timestamp,
            'status' => $message->status,
            'created_at' => $message->created_at?->toIso8601String(),
        ];
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.' . $this->chatId),
            new PrivateChannel('account.' . $this->accountId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.new';
    }

    public function broadcastWith(): array
    {
        return $this->messageData;
    }
}
