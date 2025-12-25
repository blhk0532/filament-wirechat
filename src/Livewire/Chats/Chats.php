<?php

namespace Adultdate\Wirechat\Livewire\Chats;

use AdultDate\FilamentWirechat\Filament\Pages\ChatPage;
use AdultDate\FilamentWirechat\Models\Conversation;
use Adultdate\Wirechat\Helpers\MorphClassResolver;
use Adultdate\Wirechat\Livewire\Concerns\HasPanel;
use Adultdate\Wirechat\Livewire\Concerns\Widget;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Chats Component
 *
 * Handles chat conversations, search, and real-time updates.
 *
 * @property \Illuminate\Contracts\Auth\Authenticatable|null $auth
 */
class Chats extends Component
{
    use HasPanel,Widget;

    /**
     * The search query.
     *
     * @var mixed
     */
    public $search;

    /**
     * The list of conversations.
     *
     * @var \Illuminate\Support\Collection|array
     */
    public $conversations = [];

    /**
     * Features
     */
    #[Locked]
    public ?bool $createChatAction = null;

    #[Locked]
    public ?bool $chatsSearch = null;

    #[Locked]
    public ?bool $redirectToHomeAction = null;

    #[Locked]
    public ?string $heading = '';

    /**
     * Indicates if more conversations can be loaded.
     */
    public bool $canLoadMore = false;

    /**
     * The current page for pagination.
     *
     * @var int
     */
    public $page = 1;

    /**
     * The ID of the selected conversation.
     *
     * @var mixed
     */
    public $selectedConversationId;

    /**
     * Returns an array of event listeners.
     *
     * @return array
     */
    public function getListeners()
    {
        $user = $this->auth;
        $encodedType = MorphClassResolver::encode($user?->getMorphClass());
        $userId = $user?->getKey();

        $listeners = [
            'refresh' => '$refresh',
            'hardRefresh',
        ];

        if ($this->panel() == null) {
            \Illuminate\Support\Facades\Log::warning('Wirechat:No panels registered in Chat Component');
        } else {
            $panelId = $this->panel()->getId();
            // Construct the channel name using the encoded type and user ID.
            $channelName = "$panelId.participant.$encodedType.$userId";
            $listeners["echo-private:{$channelName},.Wirechat\\Wirechat\\Events\\NotifyParticipant"] = 'refreshComponent';
        }

        return $listeners;
    }

    /**
     * Forces the conversation list to reset as if it was newly opened.
     *
     * @return void
     */
    public function hardRefresh()
    {
        $this->conversations = collect();
        $this->reset(['page', 'canLoadMore']);
    }

    /**
     * Refreshes the chats by resetting the conversation list and pagination.
     *
     * @return void
     */
    #[On('refresh-chats')]
    public function refreshChats()
    {
        $this->conversations = collect();
        $this->reset(['page', 'canLoadMore']);
    }

    /**
     * Handle the 'chat-deleted' event.
     *
     * @param  mixed  $conversationId  The ID of the deleted conversation.
     * @return void
     */
    #[On('chat-deleted')]
    public function chatDeleted($conversationId)
    {
        $this->conversations = $this->conversations->reject(function ($conversation) use ($conversationId) {
            return $conversation->id === $conversationId;
        });
    }

    /**
     * Handle the 'chat-exited' event.
     *
     * @param  mixed  $conversationId  The ID of the exited conversation.
     * @return void
     */
    #[On('chat-exited')]
    public function chatExited($conversationId)
    {
        $this->conversations = $this->conversations->reject(function ($conversation) use ($conversationId) {
            return $conversation->id === $conversationId;
        });
    }

    /**
     * Refreshes the component if the event's conversation ID does not match the selected conversation.
     *
     * @param  array  $event  Event data containing message and conversation details.
     * @return void
     */
    public function refreshComponent($event)
    {
        if ($event['message']['conversation_id'] != $this->selectedConversationId) {
            $this->dispatch('refresh')->self();
            // Dispatch event to update unread count badge
            $this->dispatch('refresh-unread-count');
        }
    }

    /**
     * Loads more conversations if available.
     *
     * @return void|null
     */
    public function loadMore()
    {
        // Check if no more conversations are available.
        if (! $this->canLoadMore) {
            return null;
        }

        // Load the next page.
        $this->page++;
    }

    /**
     * Resets conversations and pagination when the search query is updated.
     *
     * @param  mixed  $value  The new search query.
     * @return void
     */
    public function updatedSearch($value)
    {
        $this->conversations = []; // Clear previous results when a new search is made.
        $this->reset(['page', 'canLoadMore']);
    }

    /**
     * Loads conversations based on the current page and search filters.
     * Applies search filters and updates the conversations collection.
     *
     * @return void
     */
    protected function loadConversations()
    {
        $perPage = 10;
        $offset = ($this->page - 1) * $perPage;

        $additionalConversations = $this->auth->conversations()
            ->with([
                'lastMessage.participant.participantable',
                'group.cover' => fn ($query) => $query->select('id', 'url', 'attachable_type', 'attachable_id', 'file_path'),
            ])
            ->when(trim($this->search ?? '') != '', fn ($query) => $this->applySearchConditions($query))
            ->when(trim($this->search ?? '') == '', function ($query) {
                /** @phpstan-ignore-next-line */
                return $query->withoutDeleted()->withoutBlanks();
            })
            ->latest('updated_at')
            ->skip($offset)
            ->take($perPage)
            ->get();

        // Set participants manually where needed
        $additionalConversations->each(function ($conversation) {
            if ($conversation->isPrivate() || $conversation->isSelf()) {
                // Manually load participants (only 2 expected in private/self)
                $participants = $conversation->participants()->select('id', 'participantable_id', 'participantable_type', 'conversation_id', 'conversation_read_at')->with('participantable')->get();
                $conversation->setRelation('participants', $participants);

                // Set peer and auth participants
                $conversation->auth_participant = $conversation->participant($this->auth);
                $conversation->peer_participant = $conversation->peerParticipant($this->auth);
            }
        });

        $this->canLoadMore = $additionalConversations->count() === $perPage;

        $this->conversations = collect($this->conversations)
            ->concat($additionalConversations)
            ->unique('id')
            ->sortByDesc('updated_at')
            ->values();
    }

    /**
     * Eager loads additional conversation relationships.
     *
     * @return void
     */
    public function hydrateConversations()
    {
        $this->conversations->map(function ($conversation) {
            // Only load participants manually if not a group
            if (! $conversation->isGroup()) {
                $participants = $conversation->participants()->select('id', 'participantable_id', 'participantable_type', 'conversation_id', 'conversation_read_at')->with(['participantable', 'actions'])->get();

                $conversation->setRelation('participants', $participants);

                // Set peer and auth participants
                $conversation->auth_participant = $conversation->participant($this->auth);
                $conversation->peer_participant = $conversation->peerParticipant(reference: $this->auth);
            }

            return $conversation->loadMissing([
                'lastMessage',
                'group.cover' => fn ($query) => $query->select('id', 'url', 'attachable_type', 'attachable_id', 'file_path'),
            ]);
        });
    }

    /**
     * Returns the authenticated user.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    #[Computed(persist: true)]
    public function auth()
    {
        return auth()->user();
    }

    /**
     * Applies search conditions to the conversations query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  The query builder instance.
     */
    protected function applySearchConditions($query): \Illuminate\Database\Eloquent\Builder
    {
        $searchableFields = $this->panel()->getSearchableAttributes();
        $groupSearchableFields = ['name', 'description'];
        $columnCache = [];

        // Use withDeleted to reverse withoutDeleted in order to make deleted chats appear in search.
        /** @phpstan-ignore-next-line */
        return $query->withDeleted()->where(function ($query) use ($searchableFields, $groupSearchableFields, &$columnCache) {
            // Search in participants' participantable fields.
            $query->whereHas('participants', function ($subquery) use ($searchableFields, &$columnCache) {
                $subquery->whereHas('participantable', function ($query2) use ($searchableFields, &$columnCache) {
                    $query2->where(function ($query3) use ($searchableFields, &$columnCache) {
                        $table = $query3->getModel()->getTable();
                        foreach ($searchableFields as $field) {
                            if ($this->columnExists($table, $field, $columnCache)) {
                                $query3->orWhere($field, 'LIKE', '%'.$this->search.'%');
                            }
                        }
                    });
                });
            });

            // Search in group fields directly.
            return $query->orWhereHas('group', function ($groupQuery) use ($groupSearchableFields) {
                $groupQuery->where(function ($query4) use ($groupSearchableFields) {
                    foreach ($groupSearchableFields as $field) {
                        $query4->orWhere($field, 'LIKE', '%'.$this->search.'%');
                    }
                });
            });
        });
    }

    /**
     * Checks if a column exists in the table and caches the result.
     *
     * @param  string  $table  The name of the table.
     * @param  string  $field  The column name.
     * @param  array  $columnCache  Reference to the cache array.
     * @return bool
     */
    protected function columnExists($table, $field, &$columnCache)
    {
        if (! isset($columnCache[$table])) {
            $columnCache[$table] = Schema::getColumnListing($table);
        }

        return in_array($field, $columnCache[$table]);
    }

    /**
     * Mounts the component and initializes conversations.
     *
     * @return void
     */
    public function mount()
    {

        abort_unless(auth()->check(), 401);
        $conversation = request()->route('conversation');
        $this->selectedConversationId = $conversation ? $conversation->id : request()->conversation;
        $this->conversations = collect();

    }

    //    protected function initialize()
    //    {
    //        $this->heading = $this->panel()?->getHeading();
    //        $this->createChatAction = $this->panel()?->hasCreateChatAction();
    //        $this->chatsSearch = $this->panel()?->hasChatsSearch();
    //        $this->redirectToHomeAction = $this->widget
    //            ? false
    //            : $this->panel()?->hasRedirectToHomeAction();
    //    }

    protected function initialize()
    {
        // Grab the original class‐level defaults
        $defaults = get_class_vars(static::class);

        //
        // TITLE
        //
        // If current ≠ original (''), the user passed something:
        //   • null → explicit “no heading”
        //   • non‐empty string → custom heading
        //

        if ($this->heading !== $defaults['heading']) {
            // leave $this->heading as-is (null or custom string)
        } else {
            // still '', so never set → pull from panel()

            $this->heading = $this->panel()?->getHeading();
        }
        //  dd($this->heading , $defaults['heading']);

        //
        // BOOLEAN FLAGS
        //
        // Their default is null, so:
        //   • null → never set → fallback to panel()
        //   • true/false → explicit override
        // todo: update action names to match panel names
        if ($this->createChatAction === null) {
            $this->createChatAction = $this->panel()?->hasCreateChatAction();
        }

        if ($this->chatsSearch === null) {
            $this->chatsSearch = $this->panel()?->hasChatsSearch();
        }

        if ($this->redirectToHomeAction === null) {
            $this->redirectToHomeAction = $this->widget
                ? false
                : $this->panel()?->hasRedirectToHomeAction();
        }
    }

    /**
     * Get the chat route for a conversation.
     * For Filament pages, generates a Filament page URL.
     * For standalone wirechat, uses panel's chatRoute.
     *
     * @param  mixed  $conversation  The conversation or conversation ID
     * @param  bool  $absolute  Whether to return an absolute URL
     */
    public function chatRoute($conversation, bool $absolute = true): string
    {
        $conversationId = $conversation instanceof Conversation ? $conversation->id : $conversation;

        // Check if we're on an admin route first (most reliable check)
        $currentPath = request()->path();
        $isAdminRoute = str_starts_with($currentPath, 'admin');

        // First, try to detect if we're in a Filament context
        // Check if Filament is available and we have a current panel
        if ($isAdminRoute) {
            try {
                if (class_exists(\Filament\Facades\Filament::class)) {
                    $filamentPanel = Filament::getCurrentPanel();
                    if ($filamentPanel && class_exists(ChatPage::class)) {
                        // We're in a Filament context, use Filament page URL
                        return $filamentPanel->getPageUrl(ChatPage::class, ['conversation' => $conversationId], absolute: $absolute);
                    }
                }
            } catch (\Exception $e) {
                // Continue to fallback if Filament is not available
            }

            // If Filament panel check failed but we're on admin route, use admin path
            $path = '/admin/chats/'.$conversationId;

            return $absolute ? url($path) : $path;
        }

        // Check if we're in widget mode - if so, we don't need a route
        if ($this->isWidget() === true) {
            // Widget mode doesn't use routes, but we still need to return something
            // This shouldn't be called in widget mode, but just in case
            $path = '/admin/chats/'.$conversationId;

            return $absolute ? url($path) : $path;
        }

        // Fallback to standalone wirechat panel route (only if not on admin route)
        if ($this->panel() && ! $isAdminRoute) {
            return $this->panel()->chatRoute($conversation, $absolute);
        }

        // Ultimate fallback
        $path = '/admin/chats/'.$conversationId;

        return $absolute ? url($path) : $path;
    }

    /**
     * Get the dashboard route URL.
     * Priority order:
     * 1. Panel's homeButtonUrl() method
     * 2. Config file (filament-wirechat.dashboard_route)
     * 3. Default Filament panel
     */
    public function dashboardRoute(): string
    {
        // First, check if panel has a homeButtonUrl set
        $panel = $this->panel();
        if ($panel && method_exists($panel, 'getHomeButtonUrl')) {
            $panelHomeUrl = $panel->getHomeButtonUrl();
            if ($panelHomeUrl !== null) {
                return $this->resolveDashboardRoute($panelHomeUrl);
            }
        }

        // Second, check config file
        $configRoute = config('filament-wirechat.dashboard_route', 'default');
        if ($configRoute !== 'default' && $configRoute !== null) {
            return $this->resolveDashboardRoute($configRoute);
        }

        // Third, default to default Filament panel
        return $this->getDefaultFilamentPanelUrl();
    }

    /**
     * Resolve a dashboard route value (URL string, route name, or 'default').
     */
    protected function resolveDashboardRoute(string $route): string
    {
        // If 'default', use default Filament panel
        if ($route === 'default') {
            return $this->getDefaultFilamentPanelUrl();
        }

        // If it's a route name (contains only alphanumeric, dots, underscores, or hyphens)
        // and route exists, use route() helper
        if (preg_match('/^[a-zA-Z0-9._-]+$/', $route) && \Illuminate\Support\Facades\Route::has($route)) {
            return route($route);
        }

        // Otherwise treat it as a URL path
        return $route;
    }

    /**
     * Get the default Filament panel URL.
     */
    protected function getDefaultFilamentPanelUrl(): string
    {
        if (class_exists(\Filament\Facades\Filament::class)) {
            $defaultPanel = \Filament\Facades\Filament::getDefaultPanel();
            if ($defaultPanel) {
                return $defaultPanel->getUrl();
            }

            // Fallback to current panel if default is not available
            if (\Filament\Facades\Filament::hasCurrentPanel()) {
                $currentPanel = \Filament\Facades\Filament::getCurrentPanel();
                if ($currentPanel) {
                    return $currentPanel->getUrl();
                }
            }
        }

        // Ultimate fallback
        return '/';
    }

    /**
     * Loads conversations and renders the view.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        $this->loadConversations();

        $this->initialize();

        return view('wirechat::livewire.chats.chats');
    }
}
