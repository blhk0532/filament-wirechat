<?php

namespace AdultDate\FilamentWirechat\Filament\Pages;

use Filament\Pages\Page;
use AdultDate\FilamentWirechat\Livewire\Chat\Chat as ChatComponent;
use AdultDate\FilamentWirechat\Models\Conversation;

class ChatPage extends Page
{
    protected string $view = 'filament-wirechat::livewire.pages.chat';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'chats/{conversation}';

    public Conversation $conversation;

    public function mount(Conversation $conversation): void
    {
        // Ensure user is authenticated
        abort_unless(auth()->check(), 401);

        // Use route model binding - Filament will automatically resolve the Conversation
        $this->conversation = $conversation;

        // Check if the user belongs to the conversation
        abort_unless(auth()->user()->belongsToConversation($this->conversation), 403);
    }

    public function getTitle(): string
    {
        if ($this->conversation->isGroup() && $this->conversation->group) {
            return $this->conversation->group->name ?? 'Group Chat';
        }

        if ($this->conversation->isPrivate()) {
            $peer = $this->conversation->peerParticipant(auth()->user());
            if ($peer && $peer->participantable) {
                return $peer->participantable->wirechat_name ?? 'Private Chat';
            }
        }

        return 'Chat';
    }

    protected function getViewData(): array
    {
        return [
            'chatComponent' => ChatComponent::class,
            'conversation' => $this->conversation,
        ];
    }
}
