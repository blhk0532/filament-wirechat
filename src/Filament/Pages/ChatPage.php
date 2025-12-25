<?php

namespace AdultDate\FilamentWirechat\Filament\Pages;

use AdultDate\FilamentWirechat\Livewire\Chat\Chat as ChatComponent;
use AdultDate\FilamentWirechat\Models\Conversation;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class ChatPage extends Page
{
    protected string $view = 'filament-wirechat::livewire.pages.chat';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = ' ';

    protected static ?string $slug = 'chats/{conversation}';

    public ?Conversation $conversation = null;

    public function mount(Conversation $conversation): void
    {
        // Ensure user is authenticated
        abort_unless(Auth::check(), 401);

        // Use route model binding - Filament will automatically resolve the Conversation
        $this->conversation = $conversation;

        // Check if the user belongs to the conversation
        abort_unless(
            $this->conversation->participants()->where('participantable_id', Auth::id())->where('participantable_type', get_class(Auth::user()))->exists(),
            403
        );
    }

    public function getTitle(): string
    {
        if (! $this->conversation) {
            return ' ';
        }

        if ($this->conversation->isGroup() && $this->conversation->group) {
            return $this->conversation->group->name ?? 'Group Chat';
        }

        if ($this->conversation->isPrivate()) {
            $peer = $this->conversation->peerParticipant(Auth::user());
            if ($peer && $peer->participantable) {
                return $peer->participantable->wirechat_name ?? 'Private Chat';
            }
        }

        return ' ';
    }

    public function getHeading(): string
    {
        return ' ';
    }

    protected function getViewData(): array
    {
        return [
            'chatComponent' => ChatComponent::class,
            'conversation' => $this->conversation,
        ];
    }
}
