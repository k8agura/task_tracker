<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageSent implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(public Message $message)
    {
        $this->message = $message->load(['user.department', 'attachments', 'chat']);
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('task.' . $this->message->chat->task_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'chat.message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'chat_id' => $this->message->chat_id,
            'message' => $this->message->message,
            'created_at' => $this->message->created_at,
            'user' => $this->message->user,
            'attachments' => $this->message->attachments,
            'task_id' => $this->message->chat->task_id,
        ];
    }
}