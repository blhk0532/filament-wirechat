<?php

namespace AdultDate\FilamentWirechat\Events;

use Carbon\Carbon;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use AdultDate\FilamentWirechat\Facades\Wirechat;
use AdultDate\FilamentWirechat\Models\Message;
use Filament\Facades\Filament;

class MessageCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithQueue, InteractsWithSockets, Queueable, SerializesModels;

    public $message;
    public ?string $panelId = null;

    public function __construct(Message $message, ?string $panel = null)
    {
        $this->message = $message->loadMissing('sendable', 'parent.sendable', 'attachment');
        
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

    public function broadcastWhen(): bool
    {
        // Check if the message is not older than 1 minutes
        $isNotExpired = Carbon::parse($this->message->created_at)->gt(Carbon::now()->subMinute());

        return $isNotExpired;
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
        return [
            'message' => [
                'id' => $this->message->id,
                'conversation_id' => $this->message->conversation_id,
            ],

        ];
    }
}
