<x-filament::icon-button
    icon="heroicon-o-chat-bubble-left-right"
    tooltip="Chats"
    color="gray"
    size="lg"
    :badge-color="$this->unreadCount > 0 ? 'danger' : null"
    x-on:click.prevent="$dispatch('open-modal', { id: 'chats-sidebar' })"
    wire:key="chats-icon-button-{{ $this->unreadCount }}"
>
    @if($this->unreadCount > 0)
        <x-slot name="badge">
            {{ $this->unreadCount > 99 ? '99+' : $this->unreadCount }}
        </x-slot>
    @endif
</x-filament::icon-button>