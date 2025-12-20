<?php

namespace AdultDate\FilamentWirechat\Events;

use Carbon\Carbon;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use AdultDate\FilamentWirechat\Facades\Wirechat;
use AdultDate\FilamentWirechat\Helpers\MorphClassResolver;
use AdultDate\FilamentWirechat\Http\Resources\MessageResource;
use AdultDate\FilamentWirechat\Models\Message;
use AdultDate\FilamentWirechat\Models\Participant;
use Filament\Facades\Filament;

class NotifyParticipant implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $participantType;
    public $participantId;
    public ?string $panelId = null;

    public function __construct(public Participant|Model $participant, public Message $message, ?string $panel = null)
    {
        if ($participant instanceof Participant) {
            $this->participantType = $participant->participantable_type;
            $this->participantId = $participant->participantable_id;
        } else {
            $this->participantType = $participant->getMorphClass();
            $this->participantId = $participant->getKey();
        }

        // Get current Filament panel ID
        $currentPanel = Filament::getCurrentPanel();
        $this->panelId = $panel ?? ($currentPanel ? $currentPanel->getId() : 'default');
        
        $message->load('conversation.group', 'sendable', 'attachment');
    }

    /**
     * The name of the queue on which to place the broadcasting job.
     */
    public function broadcastQueue(): string
    {
        return Wirechat::messagesQueue();
    }

    public function broadcastWhen(): bool
    {
        // Check if the message is not older than 60 seconds
        $isNotExpired = Carbon::parse($this->message->created_at)->gt(Carbon::now()->subMinute());

        return $isNotExpired;
    }

    public function broadcastOn(): array
    {
        $encodedType = MorphClassResolver::encode($this->participantType);
        $channels = [];

        $panelId = $this->panelId;
        $channels[] = "{$panelId}.participant.{$encodedType}.{$this->participantId}";

        return array_map(function ($channelName) {
            return new PrivateChannel($channelName);
        }, $channels);
    }

    public function broadcastWith(): array
    {
        $currentPanel = Filament::getCurrentPanel();
        $chatUrl = $currentPanel ? $currentPanel->getUrl() . '/chats/' . $this->message->conversation_id : '#';

        return [
            'message' => new MessageResource($this->message),
            'redirect_url' => $chatUrl,
        ];
    }
}
