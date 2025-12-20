# Filament Wirechat Plugin

A complete Filament v4 plugin for Wirechat - real-time messaging with private chats and group conversations.

## Installation

```bash
composer require adultdate/filament-wirechat
```

Then run the installation command:

```bash
php artisan wirechat:install
```

This will:
- Publish configuration files
- Publish and run migrations
- Set up broadcasting (WebSockets)
- Configure queue
- Set up Tailwind CSS
- Register the plugin with your Filament panel

## Configuration

After installation, add the plugin to your Filament panel:

```php
use AdultDate\FilamentWirechat\FilamentWirechatPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            FilamentWirechatPlugin::make(),
        ]);
}
```

## Features

- ✅ Private & Group Chats
- ✅ Real-time messaging
- ✅ Media sharing
- ✅ Message search
- ✅ Dark mode support
- ✅ Customizable themes

## Status

This plugin is currently in development. The following components have been ported:

### ✅ Completed
- Plugin structure and service provider
- Install command with full setup (WebSockets, Queue, Tailwind)
- Configuration file
- Migrations (updated for new namespace)
- Enums (ConversationType, ParticipantRole, MessageType, GroupType, Actions)
- Basic service and facade structure

### 🚧 In Progress / TODO
- Models (Conversation, Message, Participant, Group, Attachment, Action)
- Livewire components (Chat, Chats, Modals, etc.)
- Events and Jobs
- Notifications
- Views and styling
- Broadcasting channels
- Routes

## Contributing

Contributions are welcome! Please see the contributing guidelines.

## License

MIT
