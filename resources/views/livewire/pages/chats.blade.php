<x-filament-panels::page>
    <div class="w-full h-[calc(100vh-8rem)] flex rounded-lg overflow-hidden">
        <div class="relative h-full border-r border-[var(--wc-light-border)] dark:border-[var(--wc-dark-border)] w-full md:w-[280px] lg:w-[300px] shrink-0 overflow-hidden flex flex-col">
            <livewire:filament-wirechat.chats />
        </div>
        <main class="hidden md:flex h-full flex-1 bg-[var(--wc-light-primary)] dark:bg-[var(--wc-dark-primary)] relative overflow-hidden flex-col" style="contain:content">
            <div class="m-auto text-center justify-center flex gap-3 flex-col items-center">
                <h4 class="font-medium p-2 px-3 rounded-full font-semibold bg-[var(--wc-light-secondary)] dark:bg-[var(--wc-dark-secondary)] dark:text-white dark:font-normal">
                    @lang('filament-wirechat::pages.chat.messages.welcome')
                </h4>
            </div>
        </main>
    </div>
    
    {{-- Include modal component for new chat and other modals --}}
    <livewire:filament-wirechat.modal />
</x-filament-panels::page>
