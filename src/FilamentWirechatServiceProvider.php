<?php

namespace AdultDate\FilamentWirechat;

use AdultDate\FilamentWirechat\Commands\InstallWirechatCommand;
use Filament\Facades\Filament;
use Filament\Panel;
use Filament\Support\Assets\Asset;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Vite;
use Illuminate\Filesystem\Filesystem;
use Livewire\Features\SupportTesting\Testable;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentWirechatServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-wirechat';

    public static string $viewNamespace = 'filament-wirechat';

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasCommands($this->getCommands())
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->copyAndRegisterServiceProviderInApp()
                    ->askToStarRepoOnGitHub('adultdate/filament-wirechat');
            })
            ->hasConfigFile()
            ->hasMigrations($this->getMigrations())
            ->hasViews(static::$viewNamespace)
            ->hasTranslations();

        // Publish assets
        $this->publishes([
            __DIR__ . '/../resources/dist' => public_path('vendor/filament-wirechat'),
        ], 'filament-wirechat-assets');
    }

    public function packageRegistered(): void
    {
        $this->app->singleton('wirechat', function ($app) {
            return new Services\WirechatService;
        });

    }

    public function packageBooted(): void
    {
        // Asset Registration
        FilamentAsset::register(
            $this->getAssets(),
            $this->getAssetPackageName()
        );

        FilamentAsset::registerScriptData(
            $this->getScriptData(),
            $this->getAssetPackageName()
        );

        // Icon Registration
        FilamentIcon::register($this->getIcons());

        // Register Livewire Components
        $this->registerLivewireComponents();

        // Load broadcasting channels
        $this->loadBroadcastingChannels();

        // Register Blade directive for theme CSS variables
        $this->registerThemeStyles();

        // Testing
        Testable::mixin(new Testing\TestsFilamentWirechat);
    }

    protected function loadBroadcastingChannels(): void
    {
        if (file_exists($channelsPath = __DIR__ . '/../routes/channels.php')) {
            require $channelsPath;
        }
    }

    protected function registerLivewireComponents(): void
    {
        \Livewire\Livewire::component('filament-wirechat.chats', \AdultDate\FilamentWirechat\Livewire\Chats\Chats::class);
        \Livewire\Livewire::component('filament-wirechat.chat', \AdultDate\FilamentWirechat\Livewire\Chat\Chat::class);
        \Livewire\Livewire::component('filament-wirechat.chat.drawer', \AdultDate\FilamentWirechat\Livewire\Chat\Drawer::class);
        \Livewire\Livewire::component('filament-wirechat.chat.info', \AdultDate\FilamentWirechat\Livewire\Chat\Info::class);
        \Livewire\Livewire::component('filament-wirechat.chat.group.info', \AdultDate\FilamentWirechat\Livewire\Chat\Group\Info::class);
        \Livewire\Livewire::component('filament-wirechat.chat.group.members', \AdultDate\FilamentWirechat\Livewire\Chat\Group\Members::class);
        \Livewire\Livewire::component('filament-wirechat.chat.group.add-members', \AdultDate\FilamentWirechat\Livewire\Chat\Group\AddMembers::class);
        \Livewire\Livewire::component('filament-wirechat.chat.group.permissions', \AdultDate\FilamentWirechat\Livewire\Chat\Group\Permissions::class);
        \Livewire\Livewire::component('filament-wirechat.new.chat', \AdultDate\FilamentWirechat\Livewire\New\Chat::class);
        \Livewire\Livewire::component('filament-wirechat.new.group', \AdultDate\FilamentWirechat\Livewire\New\Group::class);
        \Livewire\Livewire::component('filament-wirechat.modal', \AdultDate\FilamentWirechat\Livewire\Modals\Modal::class);
        \Livewire\Livewire::component('filament-wirechat.widget', \AdultDate\FilamentWirechat\Livewire\Widgets\Wirechat::class);
    }

    protected function getAssetPackageName(): ?string
    {
        return 'adultdate/filament-wirechat';
    }

    /**
     * @return array<Asset>
     */
    protected function getAssets(): array
    {
        return [
            // CSS will be included via the main app.css file
            // Load Laravel Echo for real-time broadcasting - must load on all pages for real-time to work
            // Reference the main application's app.js file using Vite
            Js::make('filament-wirechat-echo', Vite::asset('resources/js/app.js'))
                ->module(),
        ];
    }

    /**
     * @return array<class-string>
     */
    protected function getCommands(): array
    {
        return [
            InstallWirechatCommand::class,
        ];
    }

    /**
     * @return array<string>
     */
    protected function getIcons(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getScriptData(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getMigrations(): array
    {
        return [
            'create_wirechat_conversations_table',
            'create_wirechat_attachments_table',
            'create_wirechat_messages_table',
            'create_wirechat_participants_table',
            'create_wirechat_actions_table',
            'create_wirechat_groups_table',
        ];
    }

    /**
     * Register Blade directive for injecting theme CSS variables.
     * Uses Filament's panel colors by default, with config overrides.
     */
    protected function registerThemeStyles(): void
    {
        Blade::directive('filamentWirechatStyles', function () {
            return "<?php echo app('AdultDate\FilamentWirechat\Services\ThemeService')->renderStyles(); ?>";
        });
    }
}
