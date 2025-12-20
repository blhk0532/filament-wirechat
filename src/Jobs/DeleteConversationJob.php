<?php

namespace AdultDate\FilamentWirechat\Jobs;

use AdultDate\FilamentWirechat\Facades\Wirechat;
use AdultDate\FilamentWirechat\Models\Conversation;
use AdultDate\FilamentWirechat\Traits\InteractsWithPanel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\WithoutRelations;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeleteConversationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use InteractsWithPanel;

    /**
     * Create a new job instance.
     */
    public function __construct(
        #[WithoutRelations]
        public Conversation $conversation, ?string $panel = null)
    {
        $this->resolvePanel($panel);
        //
        // Use the notifications queue from config
        $this->onQueue(Wirechat::notificationsQueue());

        $this->delay(now()->addSeconds(5)); // Delay
    }

    public function handle()
    {

        $this->conversation->delete();

    }
}
