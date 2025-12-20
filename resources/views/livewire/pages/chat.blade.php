<div class="w-full flex h-[calc(100vh-8rem)] rounded-lg overflow-hidden">
    <div class="hidden md:flex bg-inherit border-r border-[var(--wc-light-border)] dark:border-[var(--wc-dark-border)] dark:bg-inherit relative h-full w-[280px] lg:w-[300px] shrink-0 overflow-hidden flex-col">
        <livewire:filament-wirechat.chats />
    </div>

    <main class="flex flex-1 h-full relative overflow-hidden flex-col" style="contain:content">
        <livewire:filament-wirechat.chat :conversation="$conversation->id" />
    </main>
</div>
