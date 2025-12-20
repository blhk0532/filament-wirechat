<?php

namespace AdultDate\FilamentWirechat\Contracts;

use Filament\Panel;

interface WirechatUser
{
    /**
     * Determine if the user can create new groups.
     */
    public function canCreateGroups(): bool;

    /**
     * Determine if the user can create new chats with other users.
     */
    public function canCreateChats(): bool;

    /**
     * Determine if the user can access wirechat panel.
     */
    public function canAccessWirechatPanel(Panel $panel): bool;
}
