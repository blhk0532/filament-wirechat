<?php

namespace AdultDate\FilamentWirechat\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class InstallWirechatCommand extends Command
{
    protected $signature = 'wirechat:install {--panel=}';

    protected $description = 'Install Filament Wirechat plugin and complete setup including WebSockets, Queue, and Tailwind CSS';

    public function handle(): int
    {
        $this->info('Installing Filament Wirechat Plugin...');
        $this->newLine();

        // Publish configuration
        $this->info('Publishing configuration...');
        $this->publishConfiguration();
        $this->info('Configuration published');

        // Create storage symlink
        $this->info('Creating storage symlink...');
        Artisan::call('storage:link');
        $this->info('Storage linked');

        // Publish migrations
        $this->info('Publishing migrations...');
        $this->publishMigrations();
        $this->info('Migrations published');

        // Run migrations
        $this->info('Running migrations...');
        if ($this->confirm('Run migrations now?', true)) {
            $this->call('migrate');
            $this->info('Migrations run successfully');
        } else {
            $this->warn('Migrations not run. Run manually with: php artisan migrate');
        }

        // Setup broadcasting
        $this->info('Setting up broadcasting...');
        $this->setupBroadcasting();
        $this->info('Broadcasting configured');

        // Setup queue
        $this->info('Setting up queue...');
        $this->setupQueue();
        $this->info('Queue configured');

        // Setup Tailwind CSS
        $this->info('Setting up Tailwind CSS...');
        $this->setupTailwind();
        $this->info('Tailwind CSS configured');

        // Register plugin with Filament panel
        $this->info('Registering plugin with Filament panel...');
        $this->registerPlugin();
        $this->info('Plugin registered');

        $this->newLine();
        $this->info('Filament Wirechat installed successfully!');
        $this->newLine();
        $this->comment('Next steps:');
        $this->comment('1. Start queue worker: php artisan queue:work');
        $this->comment('2. Configure broadcasting driver in .env:');
        $this->comment('   - BROADCAST_CONNECTION=pusher (requires Pusher account)');
        $this->comment('   - BROADCAST_CONNECTION=reverb (Laravel Reverb - recommended, free)');
        $this->comment('   - BROADCAST_CONNECTION=redis (requires Redis + Socket.IO server)');
        $this->comment('   - BROADCAST_CONNECTION=ably (requires Ably account)');
        $this->comment('3. Visit your Filament panel to start using Wirechat!');

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
        $this->call('vendor:publish', [
            '--tag' => 'filament-wirechat-migrations',
            '--force' => true,
        ]);
    }

    protected function setupBroadcasting(): void
    {
        $envPath = base_path('.env');
        
        if (!File::exists($envPath)) {
            $this->warn('.env file not found. Please configure broadcasting manually.');
            return;
        }

        $envContent = File::get($envPath);

        // Check if BROADCAST_DRIVER is already set
        if (!preg_match('/^BROADCAST_DRIVER=/m', $envContent)) {
            $envContent .= "\nBROADCAST_DRIVER=pusher\n";
            File::put($envPath, $envContent);
            $this->line('  → Added BROADCAST_DRIVER=pusher to .env');
        }

        // Check if PUSHER_APP_ID is set
        if (!preg_match('/^PUSHER_APP_ID=/m', $envContent)) {
            $envContent = File::get($envPath);
            $envContent .= "\nPUSHER_APP_ID=\nPUSHER_APP_KEY=\nPUSHER_APP_SECRET=\nPUSHER_APP_CLUSTER=mt1\n";
            File::put($envPath, $envContent);
            $this->line('  → Added PUSHER configuration placeholders to .env');
            $this->warn('  Please configure your Pusher credentials in .env');
        }

        // Ensure broadcasting service provider is registered
        $this->ensureBroadcastingServiceProvider();
    }

    protected function setupQueue(): void
    {
        $envPath = base_path('.env');
        
        if (!File::exists($envPath)) {
            $this->warn('.env file not found. Please configure queue manually.');
            return;
        }

        $envContent = File::get($envPath);

        // Check if QUEUE_CONNECTION is already set
        if (!preg_match('/^QUEUE_CONNECTION=/m', $envContent)) {
            $envContent .= "\nQUEUE_CONNECTION=database\n";
            File::put($envPath, $envContent);
            $this->line('  → Added QUEUE_CONNECTION=database to .env');
        }
    }

    protected function setupTailwind(): void
    {
        $cssPath = resource_path('css/app.css');
        
        if (!File::exists($cssPath)) {
            $this->warn('app.css not found. Please add @source directive manually.');
            return;
        }

        $cssContent = File::get($cssPath);
        
        // Check if wirechat source is already added
        if (!str_contains($cssContent, '@source')) {
            // Add @source directive for wirechat views
            $cssContent .= "\n@source '../../vendor/adultdate/filament-wirechat/resources/**/*.blade.php';\n";
            File::put($cssPath, $cssContent);
            $this->line('  → Added @source directive to app.css');
        } elseif (!str_contains($cssContent, 'filament-wirechat')) {
            $cssContent .= "\n@source '../../vendor/adultdate/filament-wirechat/resources/**/*.blade.php';\n";
            File::put($cssPath, $cssContent);
            $this->line('  → Added @source directive to app.css');
        }
    }

    protected function registerPlugin(): void
    {
        $panelId = $this->option('panel') ?? 'admin';
        
        $this->line("  → Plugin will be registered with panel: {$panelId}");
        $this->line('  → Add FilamentWirechatPlugin::make() to your panel configuration');
    }

    protected function ensureBroadcastingServiceProvider(): void
    {
        $bootstrapPath = base_path('bootstrap/providers.php');
        
        if (File::exists($bootstrapPath)) {
            $content = File::get($bootstrapPath);
            if (!str_contains($content, 'Illuminate\\Broadcasting\\BroadcastServiceProvider')) {
                $content = str_replace(
                    "return [\n",
                    "return [\n    Illuminate\\Broadcasting\\BroadcastServiceProvider::class,\n",
                    $content
                );
                File::put($bootstrapPath, $content);
                $this->line('  → Registered BroadcastServiceProvider');
            }
        }
    }
}
