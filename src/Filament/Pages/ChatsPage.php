<?php

namespace AdultDate\FilamentWirechat\Filament\Pages;

use AdultDate\FilamentWirechat\Livewire\Chats\Chats as ChatsComponent;
use Filament\Pages\Page;

class ChatsPage extends Page
{
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected string $view = 'filament-wirechat::livewire.pages.chats';

    protected static ?string $navigationLabel = 'Chats';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = '  ';

    protected static bool $shouldRegisterNavigation = true;

    public function getHeading(): string
    {
        return ' ';
    }

    protected static bool $fullWidth = true;

    public function mount(): void
    {
        // Ensure user is authenticated
        abort_unless(auth()->check(), 401);
    }

    protected function getViewData(): array
    {
        return [
            'chatsComponent' => ChatsComponent::class,
        ];
    }
}
