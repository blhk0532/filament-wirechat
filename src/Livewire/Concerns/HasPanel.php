<?php

namespace AdultDate\FilamentWirechat\Livewire\Concerns;

use AdultDate\FilamentWirechat\Facades\Wirechat;
use AdultDate\FilamentWirechat\Filament\Pages\ChatPage;
use AdultDate\FilamentWirechat\Http\Resources\WirechatUserResource;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Route;
use Livewire\Attributes\Computed;

trait HasPanel
{
    /**
     * Resolve and assign the panel during mount.
     */
    public function mountHasPanel(): void
    {
        // For Filament, we use the current panel from Filament facade
        // No need to initialize separately
    }

    /**
     * Get the current Filament panel.
     */
    #[Computed(cache: false)]
    public function panel(): ?\Filament\Panel
    {
        return Filament::getCurrentPanel();
    }

    /**
     * Get the panel ID for broadcasting channels.
     */
    public function panelId(): string
    {
        $panel = $this->panel();
        
        return $panel ? $panel->getId() : 'default';
    }

    /**
     * Search for chatable users and return a standardized JSON resource collection.
     */
    public function searchUsers(?string $needle)
    {
        if (blank($needle)) {
            return WirechatUserResource::collection(collect());
        }

        $searchableAttributes = $this->getSearchableAttributes();
        
        // Default search: limit 20 results and return a collection
        $users = \App\Models\User::query()
            ->where(function ($q) use ($needle, $searchableAttributes) {
                foreach ($searchableAttributes as $field) {
                    $q->orWhere($field, 'like', "%{$needle}%");
                }
            })
            ->limit(20)
            ->get();

        return WirechatUserResource::collection($users);
    }

    /**
     * Get the model's searchable fields.
     */
    public function getSearchableAttributes(): array
    {
        return config('filament-wirechat.searchable_attributes', ['name', 'email']);
    }

    /**
     * Generates a URL for the chats route.
     */
    public function chatsRoute(bool $absolute = true): string
    {
        $panel = $this->panel();
        if (!$panel) {
            return route('filament.admin.pages.chats-page', [], $absolute);
        }
        
        $panelId = $panel->getId();
        
        // Use the correct page class based on the panel
        // For wirechat panel, use WirechatChatsPage, otherwise use ChatsPage
        if ($panelId === 'wirechat') {
            $pageClass = \App\Filament\Wirechat\Pages\WirechatChatsPage::class;
        } else {
            $pageClass = \AdultDate\FilamentWirechat\Filament\Pages\ChatsPage::class;
        }
        
        return $pageClass::getUrl(panel: $panelId);
    }

    /**
     * Generates a URL for the chat show route.
     */
    public function chatRoute(mixed $conversation, bool $absolute = true): string
    {
        $panel = $this->panel();
        $panelId = $panel ? $panel->getId() : 'admin';
        
        // Extract the ID from the conversation (handle both objects and IDs)
        if (is_object($conversation)) {
            if (method_exists($conversation, 'getKey')) {
                $conversationId = $conversation->getKey();
            } elseif (isset($conversation->id)) {
                $conversationId = $conversation->id;
            } else {
                throw new \InvalidArgumentException('Cannot extract ID from conversation object');
            }
        } else {
            $conversationId = $conversation;
        }
        
        // Use the correct page class based on the panel
        // For wirechat panel, use WirechatChatPage, otherwise use ChatPage
        if ($panelId === 'wirechat') {
            $pageClass = \App\Filament\Wirechat\Pages\WirechatChatPage::class;
        } else {
            $pageClass = ChatPage::class;
        }
        
        // For route model binding, pass the conversation object if available, otherwise use ID
        $conversationParam = is_object($conversation) ? $conversation : $conversationId;
        
        // Use Filament's Page class to generate the URL with parameters
        $url = $pageClass::getUrl(['conversation' => $conversationParam], panel: $panelId);
        
        // Make absolute if requested
        if ($absolute && !str_starts_with($url, 'http')) {
            return url($url);
        }
        
        return $url;
    }

    /**
     * Get the maximum number of group members.
     */
    public function getMaxGroupMembers(): int
    {
        return Wirechat::maxGroupMembers();
    }

    /**
     * Get the media MIME types.
     */
    public function getMediaMimes(): array
    {
        return config('filament-wirechat.attachments.media_mimes', ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']);
    }

    /**
     * Get the file MIME types.
     */
    public function getFileMimes(): array
    {
        return config('filament-wirechat.attachments.file_mimes', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'csv', 'zip', 'rar', '7z', 'ppt', 'pptx', 'odt', 'ods', 'rtf']);
    }

    /**
     * Get the maximum number of uploads allowed.
     */
    public function getMaxUploads(): ?int
    {
        return config('filament-wirechat.attachments.max_uploads', 10);
    }

    /**
     * Get the maximum upload size for files.
     */
    public function getFileMaxUploadSize(): ?int
    {
        return config('filament-wirechat.attachments.file_max_upload_size', 12288);
    }

    /**
     * Get the maximum upload size for media.
     */
    public function getMediaMaxUploadSize(): ?int
    {
        return config('filament-wirechat.attachments.media_max_upload_size', 12288);
    }
}
