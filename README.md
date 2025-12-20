# Filament Wirechat Plugin

A complete Filament v4 plugin for Wirechat - real-time messaging with private chats and group conversations.

## Requirements

- PHP 8.2 or higher
- Laravel 12.x
- Filament 4.x
- Database (MySQL, PostgreSQL, or SQLite)
- Broadcasting driver configured (Pusher, Reverb, Redis, or Ably)
- Queue driver configured (Database, Redis, or other)

## Installation

### Step 1: Install the Package

Install the plugin via Composer:

```bash
composer require adultdate/filament-wirechat
```

### Step 2: Run Installation Command

Run the installation command which will automatically:

- Publish the configuration file to `config/filament-wirechat.php`
- Publish all database migrations
- Run migrations to create required database tables
- Create storage symlink for file attachments
- Configure broadcasting settings in `.env`
- Configure queue settings in `.env`
- Set up Tailwind CSS source directives
- Register BroadcastServiceProvider if needed

```bash
php artisan wirechat:install
```

The command will prompt you to run migrations. Confirm to proceed.

### Step 3: Register the Plugin

Add the plugin to your Filament panel provider. Open your panel provider file (typically `app/Providers/Filament/AdminPanelProvider.php`) and add the plugin:

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

### Step 4: Configure Broadcasting

Configure your broadcasting driver in `.env`. The plugin supports multiple broadcasting drivers:

**Option 1: Laravel Reverb (Recommended - Free and built-in)**
```env
BROADCAST_DRIVER=reverb
REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
```

**Option 2: Pusher**
```env
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=your-app-id
PUSHER_APP_KEY=your-app-key
PUSHER_APP_SECRET=your-app-secret
PUSHER_APP_CLUSTER=mt1
```

**Option 3: Redis**
```env
BROADCAST_DRIVER=redis
```

**Option 4: Ably**
```env
BROADCAST_DRIVER=ably
ABLY_KEY=your-ably-key
```

### Step 5: Configure Queue

Ensure your queue connection is configured in `.env`:

```env
QUEUE_CONNECTION=database
```

Or use Redis:

```env
QUEUE_CONNECTION=redis
```

### Step 6: Start Queue Worker

Start the queue worker to process background jobs:

```bash
php artisan queue:work
```

Or use Supervisor for production environments.

### Step 7: Configure Laravel Echo (Frontend)

Make sure your `resources/js/app.js` or main JavaScript file has Laravel Echo configured:

```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher', // or 'reverb', 'socket.io', etc.
    key: process.env.MIX_PUSHER_APP_KEY,
    cluster: process.env.MIX_PUSHER_APP_CLUSTER,
    forceTLS: true,
    wsHost: window.location.hostname,
    wsPort: 6001,
    wssPort: 6001,
    disableStats: true,
});
```

### Step 8: Build Frontend Assets

Build your frontend assets if you haven't already:

```bash
npm run build
```

Or for development:

```bash
npm run dev
```

## Configuration

After installation, you can customize the plugin behavior by editing `config/filament-wirechat.php`.

### Key Configuration Options

**Storage**
- Configure the storage disk and directory for file attachments
- Set file visibility (public/private)

**Broadcasting**
- Enable/disable real-time broadcasting
- Configure broadcasting driver

**Queue**
- Set queue connection for background jobs

**Theme**
- Customize color scheme for light and dark modes
- Override Filament panel colors

**Attachments**
- Configure allowed file types (media and documents)
- Set maximum upload size
- Set maximum number of uploads per message

**Search**
- Configure searchable user attributes for creating new chats

## Database Schema

The plugin creates the following database tables:

- `wirechat_conversations` - Stores conversation/chat records
- `wirechat_messages` - Stores all messages
- `wirechat_participants` - Tracks conversation participants
- `wirechat_attachments` - Stores file attachment metadata
- `wirechat_groups` - Stores group chat information
- `wirechat_actions` - Stores message actions (reactions, etc.)

## Features

- Private one-on-one chats
- Group conversations
- Real-time messaging with broadcasting
- File and media attachments
- Message search
- Dark mode support
- Customizable themes
- Message reactions and actions
- User presence indicators
- Typing indicators

## Usage

Once installed and configured, Wirechat will be available in your Filament panel. Users can:

1. Access chats via the navigation menu or widget
2. Create new private chats with other users
3. Create or join group conversations
4. Send messages with file attachments
5. Search through conversation history
6. Customize group settings (if admin)

## Troubleshooting

### Migrations Not Running

If migrations were not run during installation, run them manually:

```bash
php artisan migrate
```

### Storage Symlink Missing

If file attachments are not accessible, create the storage symlink:

```bash
php artisan storage:link
```

### Broadcasting Not Working

1. Verify your broadcasting driver is correctly configured in `.env`
2. Ensure your broadcasting service provider is registered in `bootstrap/providers.php`
3. Check that Laravel Echo is properly configured in your JavaScript
4. For Reverb: Start the Reverb server with `php artisan reverb:start`
5. Check browser console for connection errors

### Queue Not Processing

1. Verify `QUEUE_CONNECTION` is set in `.env`
2. Ensure queue worker is running: `php artisan queue:work`
3. For production, set up Supervisor or similar process manager
4. Check queue table exists if using database driver: `php artisan queue:table && php artisan migrate`

### Tailwind Styles Not Loading

1. Ensure `@source` directive is added to `resources/css/app.css`
2. Rebuild assets: `npm run build`
3. Verify Tailwind v4 is configured correctly

## Testing

Run the test suite:

```bash
composer test
```

## License

MIT
