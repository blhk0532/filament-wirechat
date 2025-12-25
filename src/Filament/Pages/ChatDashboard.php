<?php

namespace AdultDate\FilamentWirechat\Filament\Pages;

use AdultDate\FilamentWirechat\Filament\Widgets\WirechatWidget;
use Filament\Pages\Page;

class ChatDashboard extends Page
{
    protected static ?string $slug = 'wirechat';

    protected string $view = 'filament-wirechat::filament.pages.chat-dashboard';

    protected static ?string $title = '';

    protected static ?string $navigationLabel = 'Wirechat';

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static bool $shouldRegisterNavigation = true;

    protected function getHeaderWidgets(): array
    {
        return [
            WirechatWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [];
    }

    /**
     * Get the navigation badge for unread messages count.
     * Returns null when count is 0 so badge doesn't display.
     */
    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();
        if (! $user) {
            return null;
        }

        $unreadCount = $user->getUnreadCount() ?? 0;

        // Return null if count is 0 so badge doesn't display
        if ($unreadCount === 0) {
            return null;
        }

        // Return formatted count (cap at 99+)
        return $unreadCount > 99 ? '99+' : (string) $unreadCount;
    }

    /**
     * Get the navigation badge color.
     */
    public static function getNavigationBadgeColor(): ?string
    {
        $user = auth()->user();
        if (! $user) {
            return null;
        }

        $unreadCount = $user->getUnreadCount() ?? 0;

        // Only return color if there are unread messages
        return $unreadCount > 0 ? 'danger' : null;
    }
}
