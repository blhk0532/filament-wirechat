<?php

namespace AdultDate\FilamentWirechat\Filament\Pages\WirechatPanel;

use AdultDate\FilamentWirechat\Livewire\Chats\Chats as ChatsComponent;
use Filament\Pages\Page;
use Filament\Panel;

class WirechatChatsPage extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament-wirechat::filament.pages.wirechat-panel.chats';

    protected static ?string $title = null;

    protected static bool $fullWidth = true;

    public static function getSlug(?Panel $panel = null): string
    {
        return ''; // Return empty string to make this the root/home page
    }

    public function mount(): void
    {
        abort_unless(auth()->check(), 401);
    }

    public function getTitle(): string
    {
        return '';
    }

    /**
     * Hide the page header
     */
    public function getHeader(): ?\Illuminate\Contracts\View\View
    {
        return null;
    }

    protected function getViewData(): array
    {
        return [
            'chatsComponent' => ChatsComponent::class,
        ];
    }
}
