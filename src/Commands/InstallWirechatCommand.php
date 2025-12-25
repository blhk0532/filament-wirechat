<?php

namespace AdultDate\FilamentWirechat\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class InstallWirechatCommand extends Command
{
    protected $signature = 'wirechat:install {--panel=}';

    protected $description = 'Install Filament Wirechat plugin and complete setup including WebSockets, Queue, and Tailwind CSS';

    protected bool $hasPrompts = false;

    protected bool $chatsPanelProviderCreated = false;

    public function __construct()
    {
        parent::__construct();
        $this->hasPrompts = class_exists(\Laravel\Prompts\Prompt::class);
    }

    public function handle(): int
    {
        $this->displayBanner();

        $this->promptInfo('Installing Filament Wirechat Plugin...');
        echo "\n";

        // Publish configuration
        $this->promptInfo('Publishing configuration...');
        $this->publishConfiguration();
        $this->promptNote('Configuration published');

        // Create storage symlink
        $this->promptInfo('Creating storage symlink...');
        Artisan::call('storage:link');
        $this->promptNote('Storage linked');

        // Publish migrations
        $this->promptInfo('Publishing migrations...');
        $this->publishMigrations();
        $this->promptNote('Migrations published');

        // Run migrations
        $this->promptInfo('Running migrations...');
        if ($this->promptConfirm('Run migrations now?', default: true)) {
            try {
                $exitCode = $this->call('migrate');
                if ($exitCode === 0) {
                    $this->promptNote('Migrations run successfully');
                } else {
                    $this->promptWarning('Some migrations may have failed. Check the output above.');
                    $this->promptNote('Wirechat migrations should have run successfully.');
                    $this->promptNote('You can run migrations manually with: php artisan migrate');
                }
            } catch (\Exception $e) {
                $this->promptWarning('Migration error occurred (this may be normal if some tables already exist).');
                $this->promptWarning('Wirechat migrations should have run successfully before this error.');
                $this->promptNote('You can run migrations manually with: php artisan migrate');
            }
        } else {
            $this->promptWarning('Migrations not run. Run manually with: php artisan migrate');
        }

        // Setup broadcasting
        $this->promptInfo('Setting up broadcasting...');
        $this->setupBroadcasting();
        $this->promptNote('Broadcasting configured');

        // Setup queue
        $this->promptInfo('Setting up queue...');
        $this->setupQueue();
        $this->promptNote('Queue configured');

        // Setup Tailwind CSS
        $this->promptInfo('Setting up Tailwind CSS...');
        $this->setupTailwind();
        $this->promptNote('Tailwind CSS configured');

        // Register plugin with Filament panel
        $this->promptInfo('Registering plugin with Filament panel...');
        $this->registerPlugin();
        $this->promptNote('Plugin registered');

        // Ask if user wants to create standalone Wirechat panel (ChatsPanelProvider)
        $this->promptInfo('Standalone Wirechat Panel Setup...');
        if ($this->promptConfirm('Would you like to create a standalone Wirechat panel at /chats?', default: false)) {
            $this->createChatsPanelProvider();
            $this->promptNote('ChatsPanelProvider created successfully');
            $this->chatsPanelProviderCreated = true;
        } else {
            $this->promptNote('Standalone Wirechat panel skipped. You can create it later if needed.');
        }

        echo "\n";
        $this->promptInfo('🎉 Filament Wirechat installed successfully!');
        echo "\n";

        $this->promptInfo('📋 Next Steps:');
        echo "\n";

        $this->promptNote('1️⃣  Update Your User Model');
        $this->promptNote('   → Add InteractsWithWirechat trait and implement WirechatUser contract');
        $this->promptNote('   → See README.md for detailed instructions');
        echo "\n";

        $this->promptNote('2️⃣  Register Plugin in Filament Panel');
        $this->promptNote('   → Open: app/Providers/Filament/AdminPanelProvider.php');
        $this->promptNote('   → Add: FilamentWirechatPlugin::make() to ->plugins([])');
        $this->promptNote('   → Add: ->databaseNotifications() to enable notifications bell');
        echo "\n";

        $this->promptNote('3️⃣  Configure Broadcasting (Required for real-time messaging)');
        $this->promptNote('   → Set BROADCAST_DRIVER in .env file:');
        $this->promptNote('      • BROADCAST_DRIVER=reverb (Recommended - Laravel Reverb, free)');
        $this->promptNote('      • BROADCAST_DRIVER=pusher (Requires Pusher account)');
        $this->promptNote('      • BROADCAST_DRIVER=redis (Requires Redis + Socket.IO server)');
        $this->promptNote('      • BROADCAST_DRIVER=ably (Requires Ably account)');
        $this->promptNote('   → For Reverb: Start server with: php artisan reverb:start');
        echo "\n";

        $this->promptNote('4️⃣  Start Queue Worker (Required for background jobs)');
        $this->promptNote('   → Development: php artisan queue:work');
        $this->promptNote('   → Production: Use Supervisor or similar process manager');
        echo "\n";

        $this->promptNote('5️⃣  Build Frontend Assets');
        $this->promptNote('   → Development: npm run dev');
        $this->promptNote('   → Production: npm run build');
        echo "\n";

        $this->promptInfo('🚀 You\'re all set! Visit your Filament panel to start using Wirechat!');
        $this->promptNote('   → Access chats via navigation or widget in your admin panel');
        if ($this->chatsPanelProviderCreated) {
            $this->promptNote('   → Standalone panel available at: /chats');
        }
        echo "\n";

        return self::SUCCESS;
    }

    protected function publishConfiguration(): void
    {
        $this->call('vendor:publish', [
            '--tag' => 'filament-wirechat-config',
            '--force' => true,
        ]);
    }

    protected function publishMigrations(): void
    {
        // Publish migrations using the tag
        $result = $this->call('vendor:publish', [
            '--tag' => 'filament-wirechat-migrations',
            '--force' => true,
        ]);

        // Verify migrations were published
        $migrationFiles = [
            '2024_11_01_000001_create_wirechat_conversations_table.php',
            '2024_11_01_000002_create_wirechat_attachments_table.php',
            '2024_11_01_000003_create_wirechat_messages_table.php',
            '2024_11_01_000004_create_wirechat_participants_table.php',
            '2024_11_01_000006_create_wirechat_actions_table.php',
            '2024_11_01_000007_create_wirechat_groups_table.php',
        ];

        $migrationsPath = database_path('migrations');
        $missingMigrations = [];

        foreach ($migrationFiles as $migrationFile) {
            $migrationPath = $migrationsPath . '/' . $migrationFile;
            if (! file_exists($migrationPath)) {
                $missingMigrations[] = $migrationFile;
            }
        }

        if (! empty($missingMigrations)) {
            $this->promptWarning('Some migrations may not have been published:');
            foreach ($missingMigrations as $migration) {
                $this->promptWarning("  - {$migration}");
            }
        } else {
            $this->promptNote('  → All migrations published successfully');
        }
    }

    protected function setupBroadcasting(): void
    {
        $envPath = base_path('.env');

        if (! File::exists($envPath)) {
            $this->promptWarning('.env file not found. Please configure broadcasting manually.');

            return;
        }

        $envContent = File::get($envPath);

        // Check if BROADCAST_DRIVER is already set
        if (! preg_match('/^BROADCAST_DRIVER=/m', $envContent)) {
            $envContent .= "\nBROADCAST_DRIVER=pusher\n";
            File::put($envPath, $envContent);
            $this->promptNote('  → Added BROADCAST_DRIVER=pusher to .env');
        }

        // Check if PUSHER_APP_ID is set
        if (! preg_match('/^PUSHER_APP_ID=/m', $envContent)) {
            $envContent = File::get($envPath);
            $envContent .= "\nPUSHER_APP_ID=\nPUSHER_APP_KEY=\nPUSHER_APP_SECRET=\nPUSHER_APP_CLUSTER=mt1\n";
            File::put($envPath, $envContent);
            $this->promptNote('  → Added PUSHER configuration placeholders to .env');
            $this->promptWarning('  Please configure your Pusher credentials in .env');
        }

        // Ensure broadcasting service provider is registered
        $this->ensureBroadcastingServiceProvider();
    }

    protected function setupQueue(): void
    {
        $envPath = base_path('.env');

        if (! File::exists($envPath)) {
            $this->promptWarning('.env file not found. Please configure queue manually.');

            return;
        }

        $envContent = File::get($envPath);

        // Check if QUEUE_CONNECTION is already set
        if (! preg_match('/^QUEUE_CONNECTION=/m', $envContent)) {
            $envContent .= "\nQUEUE_CONNECTION=database\n";
            File::put($envPath, $envContent);
            $this->promptNote('  → Added QUEUE_CONNECTION=database to .env');
        }
    }

    protected function setupTailwind(): void
    {
        $cssPath = resource_path('css/app.css');

        if (! File::exists($cssPath)) {
            $this->promptWarning('app.css not found. Please add @source directive manually.');
            $this->promptNote('  → Add this line to your app.css:');
            $this->promptNote('  → @source "../../vendor/adultdate/filament-wirechat/resources/**/*.blade.php";');

            return;
        }

        $cssContent = File::get($cssPath);
        $wirechatSource = "@source '../../vendor/adultdate/filament-wirechat/resources/**/*.blade.php';";

        // Check if wirechat source is already added
        if (str_contains($cssContent, 'filament-wirechat')) {
            $this->promptNote('  → Tailwind CSS source already configured');
        } elseif (! str_contains($cssContent, '@source') && ! str_contains($cssContent, '@import')) {
            // For Tailwind v4, add @source directive for wirechat views
            $cssContent .= "\n{$wirechatSource}\n";
            File::put($cssPath, $cssContent);
            $this->promptNote('  → Added @source directive to app.css');
        } elseif (str_contains($cssContent, '@source') && ! str_contains($cssContent, 'filament-wirechat')) {
            // If @source exists but wirechat source is missing, add it
            $cssContent .= "\n{$wirechatSource}\n";
            File::put($cssPath, $cssContent);
            $this->promptNote('  → Added @source directive to app.css');
        } else {
            $this->promptNote('  → Tailwind CSS source already configured');
        }
    }

    protected function displayBanner(): void
    {
        $this->line('');
        $this->line('<fg=#f59e0b;options=bold>███████╗██╗██╗      █████╗ ███╗   ███╗███████╗███╗   ██╗████████╗</>');
        $this->line('<fg=#f59e0b;options=bold>██╔════╝██║██║     ██╔══██╗████╗ ████║██╔════╝████╗  ██║╚══██╔══╝</>');
        $this->line('<fg=#f59e0b;options=bold>█████╗  ██║██║     ███████║██╔████╔██║█████╗  ██╔██╗ ██║   ██║   </>');
        $this->line('<fg=#f59e0b;options=bold>██╔══╝  ██║██║     ██╔══██║██║╚██╔╝██║██╔══╝  ██║╚██╗██║   ██║   </>');
        $this->line('<fg=#f59e0b;options=bold>██║     ██║███████╗██║  ██║██║ ╚═╝ ██║███████╗██║ ╚████║   ██║   </>');
        $this->line('<fg=#f59e0b;options=bold>╚═╝     ╚═╝╚══════╝╚═╝  ╚═╝╚═╝     ╚═╝╚══════╝╚═╝  ╚═══╝   ╚═╝   </>');

        $this->line('');
        $this->line('<fg=#ef4444;options=bold>██╗    ██╗██╗██████╗ ███████╗ ██████╗██╗  ██╗ █████╗ ████████╗</>');
        $this->line('<fg=#ef4444;options=bold>██║    ██║██║██╔══██╗██╔════╝██╔════╝██║  ██║██╔══██╗╚══██╔══╝</>');
        $this->line('<fg=#ef4444;options=bold>██║ █╗ ██║██║██████╔╝█████╗  ██║     ███████║███████║   ██║   </>');
        $this->line('<fg=#ef4444;options=bold>██║███╗██║██║██╔══██╗██╔══╝  ██║     ██╔══██║██╔══██║   ██║   </>');
        $this->line('<fg=#ef4444;options=bold>╚███╔███╔╝██║██║  ██║███████╗╚██████╗██║  ██║██║  ██║   ██║   </>');
        $this->line('<fg=#ef4444;options=bold> ╚══╝╚══╝ ╚═╝╚═╝  ╚═╝╚══════╝ ╚═════╝╚═╝  ╚═╝╚═╝  ╚═╝   ╚═╝   </>');

        $this->line('');
        $this->line('<fg=gray>────────────────────────────────────────────────────────</>');
        $this->line('<fg=gray>Wirechat – Simple Chat System for Laravel Filament</>');
        $this->line('');

        $this->line('<fg=cyan>✨ If you like this package, please consider leaving a star on GitHub!</>');
        $this->line('<fg=gray>   https://github.com/adultdate/filament-wirechat</>');
        $this->line('');
        $this->line('<fg=gray>────────────────────────────────────────────────────────</>');
        $this->line('');
    }

    protected function registerPlugin(): void
    {
        $panelId = $this->option('panel') ?? 'admin';

        $this->promptNote("  → Plugin will be registered with panel: {$panelId}");
        $this->promptNote('  → Add FilamentWirechatPlugin::make() to your panel configuration');
    }

    protected function createChatsPanelProvider(): void
    {
        $providerPath = app_path('Providers/Adultdate/ChatsPanelProvider.php');
        $providersPath = base_path('bootstrap/providers.php');

        // Create the ChatsPanelProvider
        if (! File::exists($providerPath)) {
            $stub = $this->getChatsPanelProviderStub();
            File::ensureDirectoryExists(dirname($providerPath));
            File::put($providerPath, $stub);
            $this->promptNote('  → Created ChatsPanelProvider at app/Providers/Adultdate/ChatsPanelProvider.php');
        } else {
            $this->promptWarning('  → ChatsPanelProvider already exists, skipping creation');
        }

        // Register in bootstrap/providers.php
        if (File::exists($providersPath)) {
            $content = File::get($providersPath);
            $providerClass = 'App\\Providers\\Adultdate\\ChatsPanelProvider::class';

            if (! str_contains($content, $providerClass)) {
                // Try to add before BroadcastServiceProvider if it exists
                if (str_contains($content, 'Illuminate\\Broadcasting\\BroadcastServiceProvider::class')) {
                    $content = str_replace(
                        'Illuminate\\Broadcasting\\BroadcastServiceProvider::class,',
                        "    {$providerClass},\n    Illuminate\\Broadcasting\\BroadcastServiceProvider::class,",
                        $content
                    );
                } else {
                    // Add at the end before closing bracket
                    $content = str_replace(
                        '];',
                        "    {$providerClass},\n];",
                        $content
                    );
                }
                File::put($providersPath, $content);
                $this->promptNote('  → Registered ChatsPanelProvider in bootstrap/providers.php');
            } else {
                $this->promptNote('  → ChatsPanelProvider already registered in bootstrap/providers.php');
            }
        }
    }

    protected function getChatsPanelProviderStub(): string
    {
        return <<<'PHP'
<?php

namespace App\Providers\Adultdate;

use Adultdate\Wirechat\Support\Color;
use Adultdate\Wirechat\Support\Enums\EmojiPickerPosition;
use Wirechat\Wirechat\Panel;
use Wirechat\Wirechat\PanelProvider;

class ChatsPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('chats')
            ->path('chats')
            ->middleware(['web', 'auth'])
            ->guards(['web'])
            ->chatsSearch()
            ->emojiPicker(position: EmojiPickerPosition::Docked)
            ->webPushNotifications()
            ->messagesQueue('messages')
            ->eventsQueue('default')
          //   ->layout('layouts.app')
            ->attachments()
            ->fileAttachments()
            ->mediaAttachments()
            ->colors([
                'primary' => Color::Blue,
                'danger' => Color::Rose,
                'gray' => Color::Gray,
                'info' => Color::Blue,
                'success' => Color::Emerald,
                'warning' => Color::Orange,
            ])
            ->heading('Chats')
            ->favicon(url: asset('favicon.ico'))
            ->createChatAction()
            ->redirectToHomeAction()
            ->createGroupAction()
            ->maxGroupMembers(10)
            ->homeUrl('/dashboard')
            ->deleteMessageActions(false)
            ->clearChatAction()
            ->mediaMaxUploadSize(12288)
            ->maxUploads(10)
            ->serviceWorkerPath(asset('js/wirechat/sw.js'))
            ->fileMimes(['zip', 'pdf', 'txt'])
            ->mediaMimes(['png', 'jpg', 'mp4'])
            ->default();
    }
}
PHP;
    }

    protected function ensureBroadcastingServiceProvider(): void
    {
        $bootstrapPath = base_path('bootstrap/providers.php');

        if (File::exists($bootstrapPath)) {
            $content = File::get($bootstrapPath);
            if (! str_contains($content, 'Illuminate\\Broadcasting\\BroadcastServiceProvider')) {
                $content = str_replace(
                    "return [\n",
                    "return [\n    Illuminate\\Broadcasting\\BroadcastServiceProvider::class,\n",
                    $content
                );
                File::put($bootstrapPath, $content);
                $this->promptNote('  → Registered BroadcastServiceProvider');
            }
        }
    }

    /**
     * Helper methods to use Laravel Prompts if available, otherwise fall back to command methods
     */
    protected function promptInfo(string $message): void
    {
        if ($this->hasPrompts) {
            \Laravel\Prompts\info($message);
        } else {
            $this->info($message);
        }
    }

    protected function promptNote(string $message): void
    {
        if ($this->hasPrompts) {
            \Laravel\Prompts\note($message);
        } else {
            $this->line($message);
        }
    }

    protected function promptWarning(string $message): void
    {
        if ($this->hasPrompts) {
            \Laravel\Prompts\warning($message);
        } else {
            $this->warn($message);
        }
    }

    protected function promptConfirm(string $message, bool $default = true): bool
    {
        if ($this->hasPrompts) {
            return \Laravel\Prompts\confirm($message, default: $default);
        }

        return $this->confirm($message, $default);
    }
}
