<?php

namespace AdultDate\FilamentWirechat\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use AdultDate\FilamentWirechat\Facades\Wirechat;
use AdultDate\FilamentWirechat\Models\Message;
use Filament\Facades\Filament;

class MessageDeleted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public ?string $panelId = null;

    public function __construct(Message $message, ?string $panel = null)
    {
        $this->message = $message->load([]);
        
        // Get current Filament panel ID
        $currentPanel = Filament::getCurrentPanel();
        $this->panelId = $panel ?? ($currentPanel ? $currentPanel->getId() : 'default');
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\PrivateChannel>
     */
    public function broadcastOn(): array
    {
        $channels = [];

        $panelId = $this->panelId;
        $channels[] = "{$panelId}.conversation.{$this->message->conversation_id}";

        return array_map(function ($channelName) {
            return new PrivateChannel($channelName);
        }, $channels);
    }

    /**
     * The name of the queue on which to place the broadcasting job.
     */
    public function broadcastQueue(): string
    {
        return Wirechat::messagesQueue();
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        //   dd($this->message);
        return [
            'message' => [
                'id' => $this->message->id,
                'conversation_id' => $this->message->conversation_id,
                'sendable_id' => $this->message->sendable_id,
                'sendable_type' => $this->message->sendable_type,
            ],
        ];
    }
}
